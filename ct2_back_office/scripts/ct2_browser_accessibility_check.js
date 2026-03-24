const fs = require('fs');

const CT2_BASE_URL = process.env.CT2_BASE_URL;
const CT2_CHROME_JSON_LIST = process.env.CT2_CHROME_JSON_LIST;
const CT2_DESKTOP_VIEWPORT = {
  width: 1440,
  height: 1100,
};

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
    await this.send('Network.enable');
    await this.send('Runtime.enable');
    await this.send('Emulation.setDeviceMetricsOverride', {
      width: CT2_DESKTOP_VIEWPORT.width,
      height: CT2_DESKTOP_VIEWPORT.height,
      deviceScaleFactor: 1,
      mobile: false,
    });
    await this.send('Network.clearBrowserCookies');
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

async function runScenarioPrepare(client, expression) {
  if (!expression) {
    return;
  }

  const prepared = await client.evaluate(expression);
  if (!prepared) {
    fail('Scenario setup failed before collecting the keyboard focus sequence.');
  }
  await sleep(250);
}

async function collectSequenceFromCurrent(client, length) {
  const sequence = [];
  const current = await client.getActiveElementSummary();
  if (current !== null) {
    sequence.push(current);
  }

  for (let step = sequence.length; step < length; step += 1) {
    await client.tab();
    const summary = await client.getActiveElementSummary();
    if (summary !== null) {
      sequence.push(summary);
    }
  }

  return sequence;
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
  const loginSequence = await client.collectFocusSequence(5);
  console.log('[ct2-browser-a11y] Login page keyboard sequence:');
  loginSequence.forEach((entry, index) => {
    console.log(`  tab_${index + 1}: ${formatElement(entry)}`);
  });
  expectSequenceIncludes(loginSequence, 'name=username', 'Login page');
  expectSequenceIncludes(loginSequence, 'name=password', 'Login page');
  expectSequenceIncludes(loginSequence, 'text=Sign In', 'Login page');
  expectVisibleFocus(loginSequence, 'name=username', 'Login page');

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
      prepare: `
        (async () => {
          const trigger = document.querySelector('[data-bs-target="#ct2-agent-form-modal"]');
          const modal = document.getElementById('ct2-agent-form-modal');
          if (!trigger || !modal) {
            return false;
          }

          trigger.click();

          for (let attempt = 0; attempt < 20; attempt += 1) {
            if (modal.classList.contains('show')) {
              const firstField = modal.querySelector('input[name="agent_code"]');
              if (!(firstField instanceof HTMLElement)) {
                return false;
              }

              firstField.focus();
              return document.activeElement === firstField;
            }
            await new Promise((resolve) => setTimeout(resolve, 50));
          }

          return false;
        })()
      `,
      tabs: 18,
      fromCurrent: true,
      expected: ['name=agent_code', 'name=agency_name'],
      focusVisible: ['name=agent_code'],
    },
    {
      name: 'Approval decision form',
      url: `${CT2_BASE_URL}?module=approvals&action=index`,
      prepare: `
        (() => {
          const statusField = document.querySelector('select[name="approval_status"]');
          if (!(statusField instanceof HTMLElement)) {
            return false;
          }

          statusField.focus();
          return document.activeElement === statusField;
        })()
      `,
      tabs: 3,
      fromCurrent: true,
      expected: ['name=approval_status', 'name=decision_notes', 'text=Save'],
      focusVisible: ['name=decision_notes'],
    },
    {
      name: 'Visa upload workflow',
      url: `${CT2_BASE_URL}?module=visa&action=index`,
      prepare: `
        (async () => {
          const trigger = document.querySelector('[data-bs-target="#ct2-visa-document-modal"]');
          const modal = document.getElementById('ct2-visa-document-modal');
          if (!trigger || !modal) {
            return false;
          }

          trigger.click();

          for (let attempt = 0; attempt < 20; attempt += 1) {
            if (modal.classList.contains('show')) {
              const firstField = modal.querySelector('select[name="ct2_visa_application_id"]');
              if (!(firstField instanceof HTMLElement)) {
                return false;
              }

              firstField.focus();
              return document.activeElement === firstField;
            }
            await new Promise((resolve) => setTimeout(resolve, 50));
          }

          return false;
        })()
      `,
      tabs: 9,
      fromCurrent: true,
      expected: ['name=ct2_document_file'],
      focusVisible: ['name=ct2_document_file'],
    },
    {
      name: 'Financial export trigger',
      url: `${CT2_BASE_URL}?module=financial&action=index&ct2_report_run_id=1&source_module=suppliers`,
      prepare: `
        (() => {
          const reportField = document.querySelector('select[name="ct2_financial_report_id"]');
          if (!(reportField instanceof HTMLElement)) {
            return false;
          }

          reportField.focus();
          return document.activeElement === reportField;
        })()
      `,
      tabs: 6,
      fromCurrent: true,
      expected: ['name=ct2_financial_report_id', 'text=Export CSV'],
      focusVisible: ['text=Export CSV'],
    },
  ];

  for (const scenario of scenarios) {
    await client.navigate(scenario.url);
    await runScenarioPrepare(client, scenario.prepare);
    const sequence = scenario.fromCurrent
      ? await collectSequenceFromCurrent(client, scenario.tabs)
      : await client.collectFocusSequence(scenario.tabs);

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
