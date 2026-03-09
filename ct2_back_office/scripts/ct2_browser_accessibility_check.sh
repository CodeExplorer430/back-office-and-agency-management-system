#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_BASE_URL="http://127.0.0.1:8097/ct2_index.php"
CT2_SERVER_PORT="8097"
CT2_CHROME_PORT="9224"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_CHROME_LOG="$CT2_TMP_DIR/ct2_chrome.log"
CT2_SERVER_PID=""
CT2_CHROME_PID=""

cleanup() {
    if [[ -n "$CT2_CHROME_PID" ]] && kill -0 "$CT2_CHROME_PID" >/dev/null 2>&1; then
        kill "$CT2_CHROME_PID" >/dev/null 2>&1 || true
        wait "$CT2_CHROME_PID" 2>/dev/null || true
    fi

    if [[ -n "$CT2_SERVER_PID" ]] && kill -0 "$CT2_SERVER_PID" >/dev/null 2>&1; then
        kill "$CT2_SERVER_PID" >/dev/null 2>&1 || true
        wait "$CT2_SERVER_PID" 2>/dev/null || true
    fi

    rm -rf "$CT2_TMP_DIR"
}

trap cleanup EXIT

log() {
    printf '[ct2-browser-a11y] %s\n' "$1"
}

fail() {
    printf '[ct2-browser-a11y] ERROR: %s\n' "$1" >&2
    exit 1
}

start_server() {
    php -S "127.0.0.1:${CT2_SERVER_PORT}" -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
    CT2_SERVER_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o /dev/null "$CT2_BASE_URL?module=auth&action=login"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start the local CT2 PHP server. See $CT2_SERVER_LOG."
}

start_chrome() {
    google-chrome \
        --headless=new \
        --no-sandbox \
        --disable-gpu \
        --remote-debugging-port="$CT2_CHROME_PORT" \
        --user-data-dir="$CT2_TMP_DIR/chrome-profile" \
        about:blank >"$CT2_CHROME_LOG" 2>&1 &
    CT2_CHROME_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o "$CT2_TMP_DIR/chrome_targets.json" "http://127.0.0.1:${CT2_CHROME_PORT}/json/list"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start headless Chrome. See $CT2_CHROME_LOG."
}

log "Starting local CT2 PHP server."
start_server

log "Starting headless Chrome for keyboard focus validation."
start_chrome

export CT2_BASE_URL
export CT2_CHROME_JSON_LIST="$CT2_TMP_DIR/chrome_targets.json"

node <<'EOF'
const fs = require('fs');

const CT2_BASE_URL = process.env.CT2_BASE_URL;
const CT2_CHROME_JSON_LIST = process.env.CT2_CHROME_JSON_LIST;

function fail(message) {
  throw new Error(message);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

class CDPClient {
  constructor(wsUrl) {
    this.wsUrl = wsUrl;
    this.ws = null;
    this.id = 0;
    this.pending = new Map();
    this.loadResolve = null;
  }

  async connect() {
    this.ws = new WebSocket(this.wsUrl);

    this.ws.onmessage = (event) => {
      const payload = JSON.parse(event.data.toString());

      if (payload.id && this.pending.has(payload.id)) {
        const { resolve, reject } = this.pending.get(payload.id);
        this.pending.delete(payload.id);
        if (payload.error) {
          reject(new Error(payload.error.message));
        } else {
          resolve(payload.result || {});
        }
        return;
      }

      if (payload.method === 'Page.loadEventFired' && this.loadResolve !== null) {
        this.loadResolve();
        this.loadResolve = null;
      }
    };

    await new Promise((resolve, reject) => {
      this.ws.onopen = resolve;
      this.ws.onerror = reject;
    });

    await this.send('Page.enable');
    await this.send('Runtime.enable');
  }

  async close() {
    if (this.ws !== null) {
      this.ws.close();
    }
  }

  send(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = ++this.id;
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  waitForLoad() {
    return new Promise((resolve) => {
      this.loadResolve = resolve;
    });
  }

  async navigate(url) {
    const waitForLoad = this.waitForLoad();
    await this.send('Page.navigate', { url });
    await waitForLoad;
    await sleep(200);
  }

  async evaluate(expression) {
    const result = await this.send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise: true,
    });

    return result.result ? result.result.value : null;
  }

  async submit(expression) {
    const waitForLoad = this.waitForLoad();
    await this.evaluate(expression);
    await waitForLoad;
    await sleep(250);
  }

  async tab() {
    await this.send('Input.dispatchKeyEvent', {
      type: 'keyDown',
      key: 'Tab',
      code: 'Tab',
      windowsVirtualKeyCode: 9,
      nativeVirtualKeyCode: 9,
    });
    await this.send('Input.dispatchKeyEvent', {
      type: 'keyUp',
      key: 'Tab',
      code: 'Tab',
      windowsVirtualKeyCode: 9,
      nativeVirtualKeyCode: 9,
    });
    await sleep(80);
  }

  async prepareFocusCycle() {
    await this.evaluate(`
      (() => {
        if (document.activeElement) {
          document.activeElement.blur();
        }

        document.body.setAttribute('tabindex', '-1');
        document.body.focus();
        return true;
      })()
    `);
    await sleep(80);
  }

  async getActiveElementSummary() {
    return await this.evaluate(`
      (() => {
        const element = document.activeElement;
        if (!element) {
          return null;
        }

        const computed = window.getComputedStyle(element);
        const outlineWidth = parseFloat(computed.outlineWidth || '0');
        const boxShadow = computed.boxShadow || 'none';
        const focusVisible = !(computed.outlineStyle === 'none' && outlineWidth === 0 && boxShadow === 'none');
        const href = element.getAttribute('href');
        const text = (element.innerText || element.value || element.getAttribute('aria-label') || '').trim().replace(/\\s+/g, ' ').slice(0, 80);

        return {
          tag: element.tagName.toLowerCase(),
          id: element.id || '',
          name: element.getAttribute('name') || '',
          type: element.getAttribute('type') || '',
          href: href || '',
          text,
          focusVisible,
        };
      })()
    `);
  }

  async collectFocusSequence(tabCount) {
    const sequence = [];
    await this.prepareFocusCycle();
    for (let step = 0; step < tabCount; step += 1) {
      await this.tab();
      const summary = await this.getActiveElementSummary();
      if (summary !== null) {
        sequence.push(summary);
      }
    }
    return sequence;
  }
}

function formatElement(entry) {
  return [
    `tag=${entry.tag}`,
    entry.id ? `id=${entry.id}` : null,
    entry.name ? `name=${entry.name}` : null,
    entry.type ? `type=${entry.type}` : null,
    entry.href ? `href=${entry.href}` : null,
    entry.text ? `text=${entry.text}` : null,
    `focus_visible=${entry.focusVisible ? 'yes' : 'no'}`,
  ].filter(Boolean).join(' | ');
}

function expectSequenceIncludes(sequence, expected, scenarioName) {
  if (!sequence.some((entry) => formatElement(entry).includes(expected))) {
    fail(`${scenarioName} is missing expected focus target: ${expected}`);
  }
}

function expectVisibleFocus(sequence, expected, scenarioName) {
  if (!sequence.some((entry) => formatElement(entry).includes(expected) && entry.focusVisible)) {
    fail(`${scenarioName} did not retain a visible focus indicator for: ${expected}`);
  }
}

async function main() {
  const targets = JSON.parse(fs.readFileSync(CT2_CHROME_JSON_LIST, 'utf8'));
  const pageTarget = targets.find((entry) => entry.type === 'page');
  if (!pageTarget) {
    fail('No Chrome page target was available for accessibility validation.');
  }

  const client = new CDPClient(pageTarget.webSocketDebuggerUrl);
  await client.connect();

  await client.navigate(`${CT2_BASE_URL}?module=auth&action=login`);
  const loginSequence = await client.collectFocusSequence(3);
  expectSequenceIncludes(loginSequence, 'name=username', 'Login page');
  expectSequenceIncludes(loginSequence, 'name=password', 'Login page');
  expectSequenceIncludes(loginSequence, 'text=Sign In', 'Login page');
  expectVisibleFocus(loginSequence, 'name=username', 'Login page');

  console.log('[ct2-browser-a11y] Login page keyboard sequence:');
  loginSequence.forEach((entry, index) => {
    console.log(`  tab_${index + 1}: ${formatElement(entry)}`);
  });

  await client.submit(`
    (() => {
      document.querySelector('#ct2-username').value = 'ct2admin';
      document.querySelector('#ct2-password').value = 'ChangeMe123!';
      document.querySelector('form').submit();
      return true;
    })()
  `);

  const dashboardHtml = await client.evaluate('document.body.innerText');
  if (!String(dashboardHtml).includes('Back-Office Dashboard')) {
    fail('Dashboard did not render after the scripted login.');
  }

  const scenarios = [
    {
      name: 'Dashboard navigation',
      url: `${CT2_BASE_URL}?module=dashboard&action=index`,
      tabs: 6,
      expected: ['text=Sign Out', 'text=Dashboard', 'text=Agents', 'text=Suppliers'],
      focusVisible: ['text=Sign Out', 'text=Agents'],
    },
    {
      name: 'Agents data-entry form',
      url: `${CT2_BASE_URL}?module=agents&action=index`,
      tabs: 32,
      expected: ['name=search', 'name=agent_code', 'name=agency_name', 'text=Save Agent'],
      focusVisible: ['name=agent_code', 'text=Save Agent'],
    },
    {
      name: 'Approval decision form',
      url: `${CT2_BASE_URL}?module=approvals&action=index`,
      tabs: 20,
      expected: ['name=approval_status', 'name=decision_notes', 'text=Save'],
      focusVisible: ['name=approval_status', 'name=decision_notes'],
    },
    {
      name: 'Visa upload workflow',
      url: `${CT2_BASE_URL}?module=visa&action=index`,
      tabs: 60,
      expected: ['name=search', 'name=ct2_document_file', 'text=Save Checklist Update'],
      focusVisible: ['name=ct2_document_file', 'text=Save Checklist Update'],
    },
    {
      name: 'Financial export trigger',
      url: `${CT2_BASE_URL}?module=financial&action=index&ct2_report_run_id=1&source_module=suppliers`,
      tabs: 28,
      expected: ['name=ct2_financial_report_id', 'text=Export CSV'],
      focusVisible: ['name=ct2_financial_report_id', 'text=Export CSV'],
    },
  ];

  for (const scenario of scenarios) {
    await client.navigate(scenario.url);
    const sequence = await client.collectFocusSequence(scenario.tabs);

    for (const expected of scenario.expected) {
      expectSequenceIncludes(sequence, expected, scenario.name);
    }

    for (const expected of scenario.focusVisible) {
      expectVisibleFocus(sequence, expected, scenario.name);
    }

    console.log(`[ct2-browser-a11y] ${scenario.name}:`);
    sequence.forEach((entry, index) => {
      console.log(`  tab_${index + 1}: ${formatElement(entry)}`);
    });
  }

  await client.close();
  console.log('[ct2-browser-a11y] CT2 browser accessibility checks passed.');
}

main().catch((error) => {
  console.error(`[ct2-browser-a11y] ERROR: ${error.message}`);
  process.exit(1);
});
EOF
