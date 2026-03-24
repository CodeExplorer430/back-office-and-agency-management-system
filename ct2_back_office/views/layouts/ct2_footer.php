    <?php if (ct2_current_user() !== null): ?>
            </div>
        </main>
    <?php else: ?>
    </main>
    <?php endif; ?>
    <div data-ct2-modal-root></div>
    <div class="toast-container ct2-toast-container" data-ct2-toast-container>
        <?php foreach (($ct2ToastQueue ?? []) as $ct2Toast): ?>
            <div
                class="toast border-0 shadow-sm ct2-toast ct2-toast-<?= htmlspecialchars((string) $ct2Toast['type'], ENT_QUOTES, 'UTF-8'); ?> animate__animated animate__fadeInUp animate__fast"
                role="<?= $ct2Toast['type'] === 'error' ? 'alert' : 'status'; ?>"
                aria-live="<?= $ct2Toast['type'] === 'error' ? 'assertive' : 'polite'; ?>"
                aria-atomic="true"
                data-ct2-toast
                data-bs-autohide="true"
                data-bs-delay="<?= htmlspecialchars((string) $ct2Toast['delay'], ENT_QUOTES, 'UTF-8'); ?>"
            >
                <div class="toast-header ct2-toast-header">
                    <span class="ct2-toast-indicator" aria-hidden="true"></span>
                    <strong class="me-auto"><?= htmlspecialchars((string) $ct2Toast['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"><?= htmlspecialchars((string) $ct2Toast['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="<?= htmlspecialchars(ct2_asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?= htmlspecialchars(ct2_asset_url('vendor/tabler/js/tabler.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?= htmlspecialchars(ct2_asset_url('js/ct2_toasts.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php if (ct2_current_user() !== null): ?>
    <script src="<?= htmlspecialchars(ct2_asset_url('js/ct2_modals.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?= htmlspecialchars(ct2_asset_url('js/ct2_sidebar.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<?php if (ct2_current_user() !== null && (string) ($_GET['module'] ?? 'dashboard') === 'dashboard'): ?>
    <script src="<?= htmlspecialchars(ct2_asset_url('vendor/apexcharts/apexcharts.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?= htmlspecialchars(ct2_asset_url('js/ct2_dashboard.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
</body>
</html>
