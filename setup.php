<?php
/* One-time setup: creates the tables and the first admin account. Locked once any account exists. */
require __DIR__ . '/lib.php';
$pdo = db();
$msg = '';
$ok = false;
if (!$pdo) {
    $msg = 'Tidak bisa terhubung ke database. Salin config.sample.php menjadi config.php dan isi data MySQL dari hosting Anda.';
} else {
    $sql = (string)file_get_contents(__DIR__ . '/install.sql');
    foreach (array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))) as $stmt) $pdo->exec($stmt);
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        $msg = 'Setup sudah selesai. Hapus file setup.php dari hosting, lalu masuk di /admin.';
        $ok = true;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $pw = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
            $msg = 'Isi email yang valid dan kata sandi minimal 8 karakter.';
        } else {
            $pdo->prepare('INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, ?, ?)')
                ->execute([$email, password_hash($pw, PASSWORD_DEFAULT), 'admin', date('Y-m-d H:i:s')]);
            $msg = 'Akun admin dibuat. Hapus file setup.php dari hosting, lalu masuk di /admin.';
            $ok = true;
        }
    }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Setup — iCare Service</title><link rel="stylesheet" href="/admin/admin.css"></head><body>
<main class="center"><div class="card narrow">
<h1>Setup iCare Service</h1>
<?php if ($msg): ?><p class="<?= $ok ? 'ok' : 'alert' ?>"><?= e($msg) ?></p><?php endif ?>
<?php if ($pdo && !$ok): ?>
<p class="muted">Tabel database sudah dibuat. Buat akun admin pertama:</p>
<form method="post">
<label>Email<input type="email" name="email" required value="icareservice.id@gmail.com"></label>
<label>Kata sandi (min. 8)<input type="password" name="password" minlength="8" required></label>
<button class="btn primary wide">Buat akun admin</button>
</form>
<?php elseif ($ok): ?><a class="btn primary wide" href="/admin/" style="text-align:center">Ke halaman admin</a><?php endif ?>
</div></main></body></html>
