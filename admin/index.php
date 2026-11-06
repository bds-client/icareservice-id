<?php
require __DIR__ . '/auth.php';
$me = require_login();
$isAdmin = $me['role'] === 'admin';
$tab = $_GET['tab'] ?? 'teks';
$msg = $_SESSION['flash'] ?? '';
$err = '';
unset($_SESSION['flash']);

function done(string $tab, string $msg): void {
    $_SESSION['flash'] = $msg;
    header('Location: /admin/?tab=' . $tab);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'texts') {
            $out = [];
            foreach ((array)($_POST['k'] ?? []) as $i => $k) {
                $id = trim((string)($_POST['id'][$i] ?? ''));
                $en = trim((string)($_POST['en'][$i] ?? ''));
                if ($id !== '' || $en !== '') $out[$k] = ['id' => $id, 'en' => $en];
            }
            save_content('texts', (object)$out, (int)$me['id']);
            done('teks', 'Teks disimpan.');
        }
        if ($action === 'values') {
            $vals = content('values') ?? [];
            foreach (VALUE_FIELDS as $f) {
                $k = $f['key'];
                if (!empty($f['image'])) {
                    if (!empty($_POST['reset'][$k])) { $vals[$k] = ''; continue; }
                    $file = $_FILES['img']['tmp_name'][$k] ?? '';
                    if ($file && is_uploaded_file($file)) {
                        $info = @getimagesize($file);
                        $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
                        if (!$ext) throw new RuntimeException('File untuk ' . $f['label'] . ' bukan gambar PNG/JPG/WEBP/GIF.');
                        if (filesize($file) > 8 * 1024 * 1024) throw new RuntimeException('Gambar maksimal 8 MB.');
                        $name = $k . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                        if (!move_uploaded_file($file, ROOT . '/uploads/' . $name)) throw new RuntimeException('Folder uploads tidak bisa ditulisi.');
                        $vals[$k] = '/uploads/' . $name;
                    }
                } else {
                    $vals[$k] = trim((string)($_POST['val'][$k] ?? ''));
                }
            }
            save_content('values', (object)$vals, (int)$me['id']);
            done('kontak', 'Kontak & gambar disimpan.');
        }
        if ($action === 'branches' || $action === 'pricelist') {
            $data = json_decode((string)($_POST['json'] ?? ''), true);
            if (!is_array($data)) throw new RuntimeException('Data tidak valid.');
            save_content($action, $data, (int)$me['id']);
            done($action === 'branches' ? 'cabang' : 'pricelist', $action === 'branches' ? 'Cabang disimpan.' : 'Pricelist disimpan.');
        }
        if ($action === 'reset' && in_array($_POST['key'] ?? '', ['branches', 'pricelist'], true)) {
            db()->prepare('DELETE FROM site_content WHERE `key` = ?')->execute([$_POST['key']]);
            done($_POST['key'] === 'branches' ? 'cabang' : 'pricelist', 'Dikembalikan ke isi awal.');
        }
        if ($action === 'password') {
            $pw = (string)($_POST['password'] ?? '');
            if (strlen($pw) < 8) throw new RuntimeException('Kata sandi minimal 8 karakter.');
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $me['id']]);
            done('staf', 'Kata sandi Anda diganti.');
        }
        if ($isAdmin && $action === 'add_user') {
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $pw = (string)($_POST['password'] ?? '');
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
            if (strlen($pw) < 8) throw new RuntimeException('Kata sandi minimal 8 karakter.');
            $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) throw new RuntimeException('Email sudah terdaftar.');
            db()->prepare('INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, ?, ?)')
                ->execute([$email, password_hash($pw, PASSWORD_DEFAULT), $role, date('Y-m-d H:i:s')]);
            done('staf', 'Akun ' . $email . ' dibuat.');
        }
        if ($isAdmin && $action === 'role' && (int)$_POST['uid'] !== (int)$me['id']) {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff', (int)$_POST['uid']]);
            done('staf', 'Akses diubah.');
        }
        if ($isAdmin && $action === 'delete_user' && (int)$_POST['uid'] !== (int)$me['id']) {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([(int)$_POST['uid']]);
            done('staf', 'Akun dihapus.');
        }
    } catch (Throwable $ex) {
        $err = $ex instanceof RuntimeException ? $ex->getMessage() : 'Gagal menyimpan: ' . $ex->getMessage();
    }
}

admin_head('Admin');
$tabs = ['teks' => 'Teks halaman', 'kontak' => 'Kontak & gambar', 'cabang' => 'Cabang', 'pricelist' => 'Pricelist', 'staf' => 'Staf & akun'];
?>
<div class="top"><strong>iCare admin</strong><a href="/" target="_blank">Lihat website ↗</a><span class="sp"></span><span><?= e($me['email']) ?> · <?= $isAdmin ? 'admin' : 'staf' ?></span><a href="/admin/logout.php">Keluar</a></div>
<nav class="tabs"><?php foreach ($tabs as $k => $label): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= $label ?></a><?php endforeach ?></nav>
<div class="wrap">
<?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif ?>
<?php if ($err): ?><p class="alert"><?= e($err) ?></p><?php endif ?>

<?php if ($tab === 'teks'): $saved = content('texts') ?? []; $groups = [];
  foreach (json_file('site-texts.json') as $x) $groups[$x['page'] . ' · ' . $x['section']][] = $x; ?>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="action" value="texts">
  <h2>Teks halaman</h2>
  <p class="muted">Kosongkan kotak untuk memakai teks asli. Teks bahasa Inggris tampil saat pengunjung memilih EN.</p>
  <input type="search" id="text-filter" placeholder="Cari teks…">
  <?php $i = 0; foreach ($groups as $g => $items): ?>
    <h3 class="grp"><?= e($g) ?></h3>
    <?php foreach ($items as $x): $o = $saved[$x['id']] ?? []; ?>
    <div class="text-item" data-search="<?= e(mb_strtolower($x['id'] . ' ' . $x['en'])) ?>">
      <input type="hidden" name="k[<?= $i ?>]" value="<?= e($x['id']) ?>">
      <div class="grid2">
        <label>ID <span class="orig"><?= e($x['id']) ?></span><textarea name="id[<?= $i ?>]" rows="1" placeholder="<?= e($x['id']) ?>"><?= e($o['id'] ?? '') ?></textarea></label>
        <label>EN <span class="orig"><?= e($x['en']) ?></span><textarea name="en[<?= $i ?>]" rows="1" placeholder="<?= e($x['en']) ?>"><?= e($o['en'] ?? '') ?></textarea></label>
      </div>
    </div>
    <?php $i++; endforeach ?>
  <?php endforeach ?>
  <div class="savebar"><button class="btn primary">Simpan teks</button></div>
</form>
<script>
document.getElementById('text-filter').addEventListener('input', function () {
  var q = this.value.toLowerCase();
  document.querySelectorAll('.text-item').forEach(function (el) { el.style.display = el.dataset.search.indexOf(q) !== -1 ? '' : 'none'; });
});
</script>

<?php elseif ($tab === 'kontak'): ?>
<form method="post" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?><input type="hidden" name="action" value="values">
  <h2>Kontak & gambar</h2>
  <?php foreach (VALUE_FIELDS as $f): ?>
    <?php if (!empty($f['image'])): ?>
      <label><?= e($f['label']) ?><input type="file" name="img[<?= $f['key'] ?>]" accept="image/png,image/jpeg,image/webp,image/gif"></label>
      <img class="imgprev" src="<?= e(v($f['key'])) ?>" alt="">
      <label class="row" style="font-weight:400"><input type="checkbox" name="reset[<?= $f['key'] ?>]" value="1" style="width:auto;display:inline"> Kembalikan gambar asli</label>
    <?php else: ?>
      <label><?= e($f['label']) ?><input name="val[<?= $f['key'] ?>]" value="<?= e((content('values') ?? [])[$f['key']] ?? '') ?>" placeholder="<?= e($f['def']) ?>"></label>
    <?php endif ?>
  <?php endforeach ?>
  <div class="savebar"><button class="btn primary">Simpan</button></div>
</form>

<?php elseif ($tab === 'cabang' || $tab === 'pricelist'): $key = $tab === 'cabang' ? 'branches' : 'pricelist'; ?>
<form method="post" class="card" id="json-form">
  <?= csrf_field() ?><input type="hidden" name="action" value="<?= $key ?>">
  <input type="hidden" name="json" id="json-field">
  <h2><?= $tab === 'cabang' ? 'Cabang' : 'Pricelist' ?></h2>
  <p class="muted"><?= $tab === 'cabang' ? 'Nomor telepon / WhatsApp kosong = memakai nomor utama dari tab Kontak & gambar.' : 'Pilih perangkat lalu jenis perbaikan. Harga kosong tampil sebagai “—”.' ?></p>
  <div id="editor"></div>
  <div class="savebar"><button class="btn primary">Simpan <?= $tab === 'cabang' ? 'cabang' : 'pricelist' ?></button></div>
</form>
<form method="post" onsubmit="return confirm('Hapus semua perubahan dan kembali ke isi awal?')">
  <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="key" value="<?= $key ?>">
  <button class="btn danger small">Kembalikan ke isi awal</button>
</form>
<script>window.EDITOR = <?= json_encode(['type' => $key, 'data' => $key === 'branches' ? branches() : devices()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="/admin/editor.js"></script>

<?php elseif ($tab === 'staf'): ?>
<?php if ($isAdmin): $users = db()->query('SELECT id, email, role, created_at FROM users ORDER BY id')->fetchAll(); ?>
<div class="card">
  <h2>Akun staf</h2>
  <table class="list"><tr><th>Email</th><th>Akses</th><th></th></tr>
  <?php foreach ($users as $u): ?>
    <tr><td><?= e($u['email']) ?></td>
    <td><?php if ((int)$u['id'] === (int)$me['id']): ?><?= e($u['role']) ?> (Anda)<?php else: ?>
      <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="uid" value="<?= $u['id'] ?>">
      <select name="role" onchange="this.form.submit()" style="width:auto"><option value="staff" <?= $u['role'] === 'staff' ? 'selected' : '' ?>>staf</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select></form><?php endif ?></td>
    <td><?php if ((int)$u['id'] !== (int)$me['id']): ?><form method="post" onsubmit="return confirm('Hapus akun ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><button class="btn danger small">Hapus</button></form><?php endif ?></td></tr>
  <?php endforeach ?>
  </table>
</div>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="action" value="add_user">
  <h2>Tambah akun</h2>
  <p class="muted">Staf bisa mengedit isi website. Admin juga bisa mengatur akun.</p>
  <div class="grid2"><label>Email<input type="email" name="email" required></label><label>Kata sandi (min. 8)<input type="password" name="password" minlength="8" required></label></div>
  <label>Akses<select name="role"><option value="staff">staf</option><option value="admin">admin</option></select></label>
  <button class="btn primary" style="margin-top:14px">Buat akun</button>
</form>
<?php endif ?>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="action" value="password">
  <h2>Ganti kata sandi Anda</h2>
  <label>Kata sandi baru (min. 8)<input type="password" name="password" minlength="8" required></label>
  <button class="btn primary" style="margin-top:14px">Ganti</button>
</form>
<?php endif ?>
</div>
</body></html>
