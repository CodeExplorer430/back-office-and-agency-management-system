<section class="ct2-auth-screen">
    <aside class="ct2-auth-card">
        <p class="ct2-eyebrow">Staff Sign-In</p>
        <h2>CT2 Back-Office Login</h2>
        <p class="ct2-subtle">Use your assigned account to open the dashboard, review incoming CT1 records, and continue back-office processing.</p>

        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'auth', 'action' => 'login']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label" for="ct2-username">Username</label>
            <input class="ct2-input" id="ct2-username" name="username" type="text" autocomplete="username" required>

            <label class="ct2-label" for="ct2-password">Password</label>
            <input class="ct2-input" id="ct2-password" name="password" type="password" autocomplete="current-password" required>

            <button class="ct2-btn ct2-btn-primary" type="submit">Sign In</button>
        </form>
    </aside>
</section>
