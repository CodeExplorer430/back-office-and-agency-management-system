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
    await sleep(250);
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
    await sleep(100);
  }
}

function logCheck(name, details) {
  console.log(`[ct2-ui] ${name}: ${details}`);
}

function summarizeResult(result, detailKeys = []) {
  const keys = detailKeys.length > 0
    ? detailKeys
    : Object.keys(result || {}).filter((key) => !['ok', 'reason'].includes(key));

  return keys
    .filter((key) => Object.hasOwn(result || {}, key))
    .map((key) => `${key}=${result[key]}`)
    .join(' | ');
}

function assertResult(result, scenarioName, detailKeys = []) {
  if (!result || result.ok !== true) {
    const details = summarizeResult(result, detailKeys);
    const suffix = details ? ` (${details})` : '';
    fail(`${scenarioName} failed${result && result.reason ? `: ${result.reason}` : '.'}${suffix}`);
  }

  const details = summarizeResult(result, detailKeys);

  logCheck(scenarioName, details || 'pass');
}

async function loginAsAdmin(client) {
  await client.navigate(`${CT2_BASE_URL}?module=auth&action=login`);
  await client.submit(`
    (() => {
      const username = document.querySelector('#ct2-username');
      const password = document.querySelector('#ct2-password');
      const form = document.querySelector('form');

      if (!username || !password || !form) {
        return false;
      }

      username.value = 'ct2admin';
      password.value = 'ChangeMe123!';
      form.submit();
      return true;
    })()
  `);

  const dashboardText = await client.evaluate('document.body.innerText');
  if (!String(dashboardText).includes('Back-Office Dashboard')) {
    fail('Admin login did not land on the dashboard.');
  }
}

async function setSidebarState(client, state, url) {
  await client.evaluate(`
    (() => {
      try {
        window.localStorage.setItem('ct2SidebarState', ${JSON.stringify(state)});
      } catch (error) {
      }
      return true;
    })()
  `);
  await client.navigate(url);
}

async function openModal(client, url, triggerSelector, modalId) {
  await client.navigate(url);

  const result = await client.evaluate(`
    (async () => {
      const trigger = document.querySelector(${JSON.stringify(triggerSelector)});
      const modal = document.getElementById(${JSON.stringify(modalId)});

      if (!trigger || !modal) {
        return { ok: false, reason: 'Modal trigger or modal element was not found.' };
      }

      trigger.click();

      for (let attempt = 0; attempt < 30; attempt += 1) {
        if (modal.classList.contains('show')) {
          return { ok: true };
        }
        await new Promise((resolve) => setTimeout(resolve, 50));
      }

      return { ok: false, reason: 'Modal did not enter the shown state.' };
    })()
  `);

  assertResult(result, `${modalId} open`);
  await sleep(200);
  await client.tab();
}

async function seedSuppliersForPagination(client) {
  await client.navigate(`${CT2_BASE_URL}?module=suppliers&action=index&tab=directory`);

  const seedResult = await client.evaluate(`
    (async () => {
      const csrfToken = document.querySelector('#ct2-supplier-form-modal input[name="ct2_csrf_token"]')?.value || '';
      if (csrfToken === '') {
        return { ok: false, reason: 'Supplier CSRF token was not found for pagination seeding.' };
      }

      const prefix = 'UI-PAG-' + Date.now();

      for (let index = 1; index <= 11; index += 1) {
        const suffix = String(index).padStart(2, '0');
        const payload = new URLSearchParams({
          ct2_csrf_token: csrfToken,
          supplier_code: prefix + '-' + suffix,
          supplier_name: 'UI Pagination Supplier ' + suffix,
          supplier_type: 'supplier',
          primary_contact_name: 'UI Pagination Contact ' + suffix,
          contact_role_title: 'QA Lead',
          email: prefix.toLowerCase() + '-' + suffix + '@example.com',
          phone: '+63-917-555-' + String(1000 + index),
          service_category: 'QA Pagination',
          support_tier: 'standard',
          approval_status: 'pending',
          onboarding_status: 'draft',
          active_status: 'active',
          risk_level: 'low',
          internal_owner_user_id: '0',
          external_supplier_id: prefix + '-EXT-' + suffix,
          source_system: prefix,
        });

        const response = await fetch(${JSON.stringify(`${CT2_BASE_URL}?module=suppliers&action=save`)}, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          },
          body: payload.toString(),
        });

        if (!response.ok) {
          return {
            ok: false,
            reason: 'Supplier pagination seed write failed.',
            status: response.status,
            prefix,
          };
        }

        await response.text();
      }

      return { ok: true, prefix };
    })()
  `);

  assertResult(seedResult, 'Supplier pagination seed');
  return seedResult.prefix;
}

async function main() {
  const targets = JSON.parse(fs.readFileSync(CT2_CHROME_JSON_LIST, 'utf8'));
  const pageTarget = targets.find((entry) => entry.type === 'page');
  if (!pageTarget) {
    fail('No Chrome page target was available for UI regression validation.');
  }

  const client = new CDPClient(pageTarget.webSocketDebuggerUrl);
  await client.connect();
  await loginAsAdmin(client);

  const dashboardUrl = `${CT2_BASE_URL}?module=dashboard&action=index`;

  await setSidebarState(client, 'expanded', dashboardUrl);
  const expandedSidebar = await client.evaluate(`
    (() => {
      const sidebar = document.querySelector('[data-ct2-sidebar]');
      const wrapper = document.querySelector('.page.ct2-app-shell .page-wrapper');

      if (!sidebar || !wrapper) {
        return { ok: false, reason: 'Sidebar or page wrapper was not found.' };
      }

      const sidebarRect = sidebar.getBoundingClientRect();
      const wrapperRect = wrapper.getBoundingClientRect();

      return {
        ok: wrapperRect.left >= sidebarRect.right - 1,
        sidebarRight: Math.round(sidebarRect.right),
        wrapperLeft: Math.round(wrapperRect.left),
        state: document.documentElement.getAttribute('data-ct2-sidebar-state') || 'expanded',
      };
    })()
  `);
  assertResult(expandedSidebar, 'Expanded sidebar layout', ['state', 'sidebarRight', 'wrapperLeft']);

  await setSidebarState(client, 'collapsed', dashboardUrl);
  const collapsedSidebar = await client.evaluate(`
    (() => {
      const sidebar = document.querySelector('[data-ct2-sidebar]');
      const wrapper = document.querySelector('.page.ct2-app-shell .page-wrapper');
      const brand = document.querySelector('.ct2-brand-mark');
      const toggle = document.querySelector('[data-ct2-sidebar-toggle]');
      const activeIcon = document.querySelector('.ct2-tabler-sidebar .nav-link.active .ct2-nav-icon');

      if (!sidebar || !wrapper || !brand || !toggle || !activeIcon) {
        return { ok: false, reason: 'Collapsed sidebar elements were not found.' };
      }

      const sidebarRect = sidebar.getBoundingClientRect();
      const wrapperRect = wrapper.getBoundingClientRect();
      const sidebarCenter = sidebarRect.left + (sidebarRect.width / 2);
      const brandRect = brand.getBoundingClientRect();
      const toggleRect = toggle.getBoundingClientRect();
      const activeIconRect = activeIcon.getBoundingClientRect();

      const brandDelta = Math.round(Math.abs((brandRect.left + (brandRect.width / 2)) - sidebarCenter));
      const toggleDelta = Math.round(Math.abs((toggleRect.left + (toggleRect.width / 2)) - sidebarCenter));
      const activeIconDelta = Math.round(Math.abs((activeIconRect.left + (activeIconRect.width / 2)) - sidebarCenter));

      return {
        ok: wrapperRect.left >= sidebarRect.right - 1 && brandDelta <= 14 && toggleDelta <= 10 && activeIconDelta <= 12,
        sidebarRight: Math.round(sidebarRect.right),
        wrapperLeft: Math.round(wrapperRect.left),
        brandDelta,
        toggleDelta,
        activeIconDelta,
        state: document.documentElement.getAttribute('data-ct2-sidebar-state') || 'expanded',
      };
    })()
  `);
  assertResult(collapsedSidebar, 'Collapsed sidebar alignment', ['state', 'sidebarRight', 'wrapperLeft', 'brandDelta', 'toggleDelta', 'activeIconDelta']);

  const supplierPaginationPrefix = await seedSuppliersForPagination(client);
  await client.navigate(`${CT2_BASE_URL}?module=suppliers&action=index&tab=directory&search=${encodeURIComponent(supplierPaginationPrefix)}&suppliers_page=2`);
  const suppliersPagination = await client.evaluate(`
    (() => {
      const current = document.querySelector('.ct2-pagination-current');
      const links = Array.from(document.querySelectorAll('.ct2-pagination-link')).map((link) => link.getAttribute('href') || '');
      const rows = Array.from(document.querySelectorAll('.ct2-table tbody tr'));
      const matchingRows = rows.filter((row) => (row.innerText || '').includes(${JSON.stringify(supplierPaginationPrefix)})).length;
      const searchValue = document.querySelector('input[name="search"]')?.value || '';

      if (!current || links.length === 0) {
        return {
          ok: false,
          reason: 'Supplier pagination controls were not rendered.',
          rowCount: rows.length,
          matchingRows,
          searchValue,
        };
      }

      const preservedSearch = links.every((href) => href.includes(${JSON.stringify('search=' + supplierPaginationPrefix)}));
      const preservedPageParam = links.every((href) => href.includes('suppliers_page='));

      return {
        ok: current.textContent.includes('Page 2 of') && preservedSearch && preservedPageParam,
        current: current.textContent.trim(),
        rowCount: rows.length,
        matchingRows,
        searchValue,
      };
    })()
  `);
  assertResult(suppliersPagination, 'Supplier pagination state', ['current', 'rowCount', 'matchingRows', 'searchValue']);

  await client.navigate(`${CT2_BASE_URL}?module=financial&action=index&tab=reports&ct2_report_run_id=1&source_module=suppliers&reports_page=2`);
  const financialTabs = await client.evaluate(`
    (() => {
      const links = Array.from(document.querySelectorAll('.ct2-tab-link')).map((link) => ({
        text: (link.textContent || '').trim(),
        href: link.getAttribute('href') || '',
        active: link.classList.contains('is-active'),
      }));

      if (links.length === 0) {
        return { ok: false, reason: 'Financial tab links were not rendered.' };
      }

      const reportsTab = links.find((link) => link.text === 'Reports');
      const analyticsTab = links.find((link) => link.text === 'Analytics');
      const runsTab = links.find((link) => link.text === 'Runs');

      if (!reportsTab || !analyticsTab || !runsTab) {
        return { ok: false, reason: 'One or more expected financial tabs are missing.' };
      }

      const requiredParams = ['ct2_report_run_id=1', 'source_module=suppliers', 'reports_page=2'];
      const preservesParams = [reportsTab, analyticsTab, runsTab].every((link) => requiredParams.every((param) => link.href.includes(param)));

      return {
        ok: reportsTab.active && preservesParams,
        reportsHref: reportsTab.href,
        analyticsHref: analyticsTab.href,
        runsHref: runsTab.href,
      };
    })()
  `);
  assertResult(financialTabs, 'Financial tab state preservation');

  await openModal(client, `${CT2_BASE_URL}?module=suppliers&action=index`, '[data-bs-target="#ct2-supplier-form-modal"]', 'ct2-supplier-form-modal');
  const supplierModal = await client.evaluate(`
    (() => {
      const modal = document.getElementById('ct2-supplier-form-modal');
      const modalRoot = document.querySelector('[data-ct2-modal-root]');
      const dialog = modal?.querySelector('.modal-dialog');
      const body = modal?.querySelector('.modal-body');
      const footer = modal?.querySelector('.modal-footer');
      const lastField = modal?.querySelector('input[name="source_system"]');
      const submitButton = footer?.querySelector('button[form], button[type="submit"]');

      if (!modal || !modalRoot || !dialog || !body || !footer || !lastField || !submitButton) {
        return { ok: false, reason: 'Supplier modal geometry elements were not found.' };
      }

      body.scrollTop = body.scrollHeight;

      const dialogRect = dialog.getBoundingClientRect();
      const footerRect = footer.getBoundingClientRect();
      const fieldRect = lastField.getBoundingClientRect();
      const buttonRect = submitButton.getBoundingClientRect();
      const elementAtButton = document.elementFromPoint(buttonRect.left + (buttonRect.width / 2), buttonRect.top + (buttonRect.height / 2));
      const activeElement = document.activeElement;

      const centeredX = Math.abs((dialogRect.left + (dialogRect.width / 2)) - (window.innerWidth / 2)) <= 32;
      const centeredY = Math.abs((dialogRect.top + (dialogRect.height / 2)) - (window.innerHeight / 2)) <= 40;
      const footerSafe = fieldRect.bottom <= footerRect.top - 8;
      const clickable = elementAtButton === submitButton || submitButton.contains(elementAtButton);
      const focusedInside = activeElement instanceof HTMLElement && modal.contains(activeElement);

      return {
        ok: modal.parentElement === modalRoot && centeredX && centeredY && footerSafe && clickable && focusedInside,
        footerSafe,
        clickable,
        focusedInside,
        activeTag: activeElement?.tagName?.toLowerCase?.() || '',
        activeClass: activeElement?.className || '',
      };
    })()
  `);
  assertResult(supplierModal, 'Supplier modal geometry', ['footerSafe', 'clickable', 'focusedInside', 'activeTag', 'activeClass']);

  const toastAndModalStack = await client.evaluate(`
    (() => {
      const modal = document.getElementById('ct2-supplier-form-modal');
      const footerButton = modal?.querySelector('.modal-footer button[form], .modal-footer button[type="submit"]');
      const backdrop = document.querySelector('.modal-backdrop.show');

      if (!modal || !footerButton || !backdrop || typeof window.ct2Toast?.show !== 'function') {
        return { ok: false, reason: 'Modal stack prerequisites were not found.' };
      }

      const toast = window.ct2Toast.show({ type: 'info', title: 'UI Regression', message: 'Modal stack check', delay: 1000, autohide: false });
      const toastContainer = document.querySelector('[data-ct2-toast-container]');
      const toastZ = parseInt(window.getComputedStyle(toastContainer).zIndex || '0', 10);
      const modalZ = parseInt(window.getComputedStyle(modal).zIndex || '0', 10);
      const backdropZ = parseInt(window.getComputedStyle(backdrop).zIndex || '0', 10);
      const buttonRect = footerButton.getBoundingClientRect();
      const elementAtButton = document.elementFromPoint(buttonRect.left + (buttonRect.width / 2), buttonRect.top + (buttonRect.height / 2));

      toast.remove();

      return {
        ok: modalZ > toastZ && modalZ > backdropZ && (elementAtButton === footerButton || footerButton.contains(elementAtButton)),
        modalZ,
        backdropZ,
        toastZ,
      };
    })()
  `);
  assertResult(toastAndModalStack, 'Toast and modal stack order', ['modalZ', 'backdropZ', 'toastZ']);

  const supplierModalCloseState = await client.evaluate(`
    (async () => {
      const modal = document.getElementById('ct2-supplier-form-modal');
      const closeButton = modal?.querySelector('.btn-close');

      if (!modal || !closeButton) {
        return { ok: false, reason: 'Modal close elements were not found.' };
      }

      closeButton.click();

      for (let attempt = 0; attempt < 20; attempt += 1) {
        const fullyHidden = !modal.classList.contains('show') && window.getComputedStyle(modal).display === 'none';
        if (fullyHidden) {
          await new Promise((resolve) => setTimeout(resolve, 80));
          const visibleBackdrop = document.querySelector('.modal-backdrop.show');
          return {
            ok: visibleBackdrop === null,
            visibleBackdrop: visibleBackdrop !== null,
          };
        }
        await new Promise((resolve) => setTimeout(resolve, 50));
      }

      return { ok: false, reason: 'Supplier modal did not close.' };
    })()
  `);
  assertResult(supplierModalCloseState, 'Supplier modal close state', ['visibleBackdrop']);

  await openModal(client, `${CT2_BASE_URL}?module=visa&action=index`, '[data-bs-target="#ct2-visa-application-modal"]', 'ct2-visa-application-modal');
  const visaApplicationDateTime = await client.evaluate(`
    (() => {
      const modal = document.getElementById('ct2-visa-application-modal');
      const appointmentDate = modal?.querySelector('input[name="appointment_date_date"]');
      const appointmentTime = modal?.querySelector('input[name="appointment_date_time"]');
      const legacy = modal?.querySelector('input[type="datetime-local"]');

      return {
        ok: appointmentDate?.getAttribute('type') === 'date' && appointmentTime?.getAttribute('type') === 'time' && !legacy,
        appointmentDateType: appointmentDate?.getAttribute('type') || '',
        appointmentTimeType: appointmentTime?.getAttribute('type') || '',
      };
    })()
  `);
  assertResult(visaApplicationDateTime, 'Visa application split date/time', ['appointmentDateType', 'appointmentTimeType']);

  await openModal(client, `${CT2_BASE_URL}?module=visa&action=index`, '[data-bs-target="#ct2-visa-payment-modal"]', 'ct2-visa-payment-modal');
  const visaPaymentDateTime = await client.evaluate(`
    (() => {
      const modal = document.getElementById('ct2-visa-payment-modal');
      const paidDate = modal?.querySelector('input[name="paid_at_date"]');
      const paidTime = modal?.querySelector('input[name="paid_at_time"]');
      const legacy = modal?.querySelector('input[type="datetime-local"]');

      return {
        ok: paidDate?.getAttribute('type') === 'date' && paidTime?.getAttribute('type') === 'time' && !legacy,
        paidDateType: paidDate?.getAttribute('type') || '',
        paidTimeType: paidTime?.getAttribute('type') || '',
      };
    })()
  `);
  assertResult(visaPaymentDateTime, 'Visa payment split date/time', ['paidDateType', 'paidTimeType']);

  await openModal(client, `${CT2_BASE_URL}?module=availability&action=index`, '[data-bs-target="#ct2-availability-dispatch-modal"]', 'ct2-availability-dispatch-modal');
  const dispatchDateTime = await client.evaluate(`
    (() => {
      const modal = document.getElementById('ct2-availability-dispatch-modal');
      const dispatchDate = modal?.querySelector('input[name="dispatch_date"]');
      const dispatchTime = modal?.querySelector('input[name="dispatch_time"]');
      const returnDate = modal?.querySelector('input[name="return_date"]');
      const returnTime = modal?.querySelector('input[name="return_time"]');
      const legacy = modal?.querySelector('input[type="datetime-local"]');

      return {
        ok: dispatchDate?.getAttribute('type') === 'date'
          && dispatchTime?.getAttribute('type') === 'time'
          && returnDate?.getAttribute('type') === 'date'
          && returnTime?.getAttribute('type') === 'time'
          && !legacy,
        dispatchDateType: dispatchDate?.getAttribute('type') || '',
        dispatchTimeType: dispatchTime?.getAttribute('type') || '',
        returnDateType: returnDate?.getAttribute('type') || '',
        returnTimeType: returnTime?.getAttribute('type') || '',
      };
    })()
  `);
  assertResult(dispatchDateTime, 'Availability dispatch split date/time', ['dispatchDateType', 'dispatchTimeType', 'returnDateType', 'returnTimeType']);

  await client.close();
  console.log('[ct2-ui] CT2 UI regression checks passed.');
}

main().catch((error) => {
  console.error(`[ct2-ui] ERROR: ${error.message}`);
  process.exit(1);
});
