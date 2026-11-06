<?php
require dirname(__DIR__) . '/lib.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Sesi kedaluwarsa, muat ulang halaman.'); }
}

/** The signed-in user, re-read from the database on every request so removed staff lose access immediately. */
function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid']) && db()) {
        $st = db()->prepare('SELECT id, email, role FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: /admin/login.php'); exit; }
    return $u;
}

function admin_head(string $title): void { ?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title><?= e($title) ?> — iCare Service</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg"><link rel="stylesheet" href="/admin/admin.css"></head><body>
<?php }
