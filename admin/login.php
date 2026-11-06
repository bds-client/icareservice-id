<?php
require __DIR__ . '/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!db()) {
        $error = 'Database belum terhubung. Periksa config.php.';
    } else {
        // Slow down guessing: one attempt per second per session.
        if (time() - ($_SESSION['last_try'] ?? 0) < 1) sleep(1);
        $_SESSION['last_try'] = time();
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $st->execute([mb_strtolower(trim((string)($_POST['email'] ?? '')))]);
        $u = $st->fetch();
        if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            header('Location: /admin/');
            exit;
        }
        $error = 'Email atau kata sandi salah.';
    }
}
admin_head('Masuk admin');
?>
<main class="center">
  <form method="post" class="card narrow">
    <a href="/" class="muted">← iCare Service</a>
    <h1>Masuk admin</h1>
    <p class="muted">Masuk untuk mengedit website dan pricelist.</p>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif ?>
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autofocus></label>
    <label>Kata sandi<input type="password" name="password" required></label>
    <button class="btn primary wide">Masuk</button>
  </form>
</main>
</body></html>
