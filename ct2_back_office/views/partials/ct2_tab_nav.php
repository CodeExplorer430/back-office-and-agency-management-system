<?php
declare(strict_types=1);

$ct2TabParam = $ct2TabParam ?? 'tab';
$ct2TabItems = is_array($ct2Tabs ?? null) ? $ct2Tabs : [];
$ct2ActiveTab = (string) ($ct2ActiveTab ?? '');
$ct2TabQuery = $_GET;
?>
<?php if ($ct2TabItems !== []): ?>
    <nav class="ct2-tab-nav" aria-label="Workspace data views">
        <?php foreach ($ct2TabItems as $ct2TabKey => $ct2TabLabel): ?>
            <?php
            $ct2HrefQuery = $ct2TabQuery;
            $ct2HrefQuery[$ct2TabParam] = $ct2TabKey;
            ?>
            <a
                class="ct2-tab-link<?= $ct2ActiveTab === (string) $ct2TabKey ? ' is-active' : ''; ?>"
                href="<?= htmlspecialchars(ct2_url($ct2HrefQuery), ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?= htmlspecialchars((string) $ct2TabLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
