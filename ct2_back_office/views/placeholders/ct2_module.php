<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">CT2 Module Reference</p>
            <h2><?= htmlspecialchars((string) $ct2Module['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <span class="ct2-badge"><?= htmlspecialchars((string) $ct2Module['status'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Current State</h3>
        <p class="ct2-subtle"><?= htmlspecialchars((string) $ct2Module['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
    </article>
    <article class="ct2-panel">
        <h3>Integration Contract</h3>
        <ul class="ct2-checklist">
            <li>All CT2 endpoints remain `ct2_` prefixed.</li>
            <li>Shared IDs will reference external system ownership where applicable.</li>
            <li>`develop` is the integration baseline while `main` stays reserved for release-ready promotion.</li>
        </ul>
        <p><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'dashboard', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Return to dashboard</a></p>
    </article>
</section>
