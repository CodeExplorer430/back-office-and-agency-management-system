<section class="ct2-landing-hero">
    <aside class="ct2-auth-card ct2-auth-card-landing" id="ct2-login">
        <p class="ct2-eyebrow">Staff Sign-In</p>
        <h3>CT2 Back-Office Login</h3>
        <p class="ct2-subtle">Use your assigned back-office account to continue into the operational dashboard.</p>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'auth', 'action' => 'login']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label" for="ct2-username">Username</label>
            <input class="ct2-input" id="ct2-username" name="username" type="text" autocomplete="username" required>

            <label class="ct2-label" for="ct2-password">Password</label>
            <input class="ct2-input" id="ct2-password" name="password" type="password" autocomplete="current-password" required>

            <button class="ct2-btn ct2-btn-primary" type="submit">Sign In</button>
        </form>
    </aside>

    <div class="ct2-landing-copy">
        <p class="ct2-eyebrow">Operational Command Surface</p>
        <h2>Run travel operations from a single CT2 back-office workspace.</h2>
        <p class="ct2-landing-intro">
            CORE TRANSACTION 2 connects agency servicing, supplier coordination, tour availability,
            marketing approvals, visa handling, financial visibility, and governance into one
            internal platform for operations teams.
        </p>
        <div class="ct2-landing-actions">
            <a class="ct2-btn ct2-btn-primary ct2-btn-inline" href="#ct2-login">Sign In Now</a>
            <a class="ct2-btn ct2-btn-secondary ct2-btn-inline" href="#ct2-workspaces">Explore Workspaces</a>
        </div>
        <div class="ct2-landing-highlights">
            <article class="ct2-highlight-chip">
                <strong>8 connected workspaces</strong>
                <span>From dispatch planning to approvals and financial review.</span>
            </article>
            <article class="ct2-highlight-chip">
                <strong>Role-based control</strong>
                <span>Back-office teams work from one governed operational surface.</span>
            </article>
            <article class="ct2-highlight-chip">
                <strong>Daily readiness</strong>
                <span>Track bookings, campaigns, documents, suppliers, and workload in one place.</span>
            </article>
        </div>
    </div>
</section>

<section class="ct2-section" id="ct2-workspaces">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Connected Workspaces</p>
            <h2>Operations stay aligned across every CT2 workflow.</h2>
            <p class="ct2-section-copy">Each workspace is tuned for daily back-office execution while staying connected to the same operational record.</p>
        </div>
    </div>

    <div class="ct2-grid-3">
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Agent and Staff Coordination</h3>
            <p>Manage agency accounts, internal staffing, assignments, and readiness from a shared control layer.</p>
        </article>
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Supplier and Partner Oversight</h3>
            <p>Track onboarding, approvals, relationship quality, and source-system visibility for operational partners.</p>
        </article>
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Availability and Dispatch Planning</h3>
            <p>Monitor resources, conflicts, soft blocks, and dispatch execution before issues affect active tours.</p>
        </article>
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Marketing and Promotion Control</h3>
            <p>Review campaigns, referral flows, vouchers, and promotions without losing operational traceability.</p>
        </article>
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Visa and Document Assistance</h3>
            <p>Keep intake, verification, uploads, follow-ups, and status handling visible for service teams.</p>
        </article>
        <article class="ct2-module-card ct2-module-card-static">
            <h3>Financial and Approval Governance</h3>
            <p>Surface reconciliation flags, operational reporting, and approval queues from the same system context.</p>
        </article>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel ct2-story-panel">
        <div class="ct2-section-header">
            <div>
                <p class="ct2-eyebrow">Operational Rhythm</p>
                <h3>Built for continuous back-office execution</h3>
            </div>
        </div>
        <div class="ct2-story-list">
            <div class="ct2-story-step">
                <strong>1. Intake and coordinate</strong>
                <p>Capture requests, onboard agencies and suppliers, and line up the staff, resources, and documents needed to move work forward.</p>
            </div>
            <div class="ct2-story-step">
                <strong>2. Review and control</strong>
                <p>Apply approvals, inspect campaign and dispatch changes, and keep operational decisions visible across connected teams.</p>
            </div>
            <div class="ct2-story-step">
                <strong>3. Track operational posture</strong>
                <p>Use dashboard and financial visibility to understand workload, readiness, and open exceptions before they become service failures.</p>
            </div>
        </div>
    </article>

    <article class="ct2-panel ct2-story-panel">
        <div class="ct2-section-header">
            <div>
                <p class="ct2-eyebrow">Trust and Control</p>
                <h3>One governed surface for teams that need accuracy</h3>
            </div>
        </div>
        <div class="ct2-landing-pillars">
            <article class="ct2-pillar-card">
                <strong>Shared visibility</strong>
                <p>Operational modules stay connected so teams are not working from fragmented spreadsheets or isolated screens.</p>
            </article>
            <article class="ct2-pillar-card">
                <strong>Approval discipline</strong>
                <p>Changes with business impact can move through explicit review paths instead of informal handoffs.</p>
            </article>
            <article class="ct2-pillar-card">
                <strong>Audit-ready activity</strong>
                <p>CT2 is structured for traceable actions, role-based access, and clearer accountability across operations.</p>
            </article>
        </div>
    </article>
</section>
