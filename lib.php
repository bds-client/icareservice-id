<?php
/* iCare Service — shared helpers for the public pages and the admin area. */
declare(strict_types=1);

const ROOT = __DIR__;

function config(): array {
    static $c = null;
    if ($c === null) {
        $file = ROOT . '/config.php';
        $c = is_file($file) ? require $file : [];
    }
    return $c;
}

/** PDO connection, or null when the database is not reachable (public pages then use the bundled defaults). */
function db(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) return $pdo;
    $c = config();
    try {
        $pdo = new PDO($c['dsn'] ?? '', $c['db_user'] ?? null, $c['db_pass'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        error_log('iCare DB connection failed: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function json_file(string $name) {
    return json_decode((string)file_get_contents(ROOT . '/data/' . $name), true);
}

/** Saved content for a key (texts, values, pricelist, branches), or null when nothing is saved. */
function content(string $key) {
    static $all = null;
    if ($all === null) {
        $all = [];
        $pdo = db();
        if ($pdo) {
            try {
                foreach ($pdo->query('SELECT `key`, `data` FROM site_content') as $row) {
                    $all[$row['key']] = json_decode($row['data'], true);
                }
            } catch (Throwable $e) {
                error_log('iCare content read failed: ' . $e->getMessage());
            }
        }
    }
    return $all[$key] ?? null;
}

function save_content(string $key, $data, ?int $userId): void {
    $st = db()->prepare('REPLACE INTO site_content (`key`, `data`, updated_at, updated_by) VALUES (?, ?, ?, ?)');
    $st->execute([$key, json_encode($data, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $userId]);
}

function lang(): string {
    return (($_COOKIE['icare_lang'] ?? 'id') === 'en') ? 'en' : 'id';
}

/** Default English for every page text, keyed by the Indonesian default. */
function text_defaults(): array {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (json_file('site-texts.json') as $x) $map[$x['id']] = $x['en'];
    }
    return $map;
}

/** Page text: admin edits override the defaults; empty edit = original wording. */
function t(string $id): string {
    $o = (content('texts') ?? [])[$id] ?? [];
    if (lang() === 'en') return ($o['en'] ?? '') !== '' ? $o['en'] : (text_defaults()[$id] ?? $id);
    return ($o['id'] ?? '') !== '' ? $o['id'] : $id;
}

/** Bilingual text that is not in the editable list (e.g. contains a device name). */
function L(string $id, ?string $en): string {
    return (lang() === 'en' && $en !== null && $en !== '') ? $en : $id;
}

const VALUE_FIELDS = [
    ['key' => 'whatsapp', 'label' => 'Nomor WhatsApp (format 62…)', 'def' => '6289614888888'],
    ['key' => 'phone', 'label' => 'Nomor telepon yang ditampilkan', 'def' => '0896-1488-8888'],
    ['key' => 'email', 'label' => 'Email', 'def' => 'icareservice.id@gmail.com'],
    ['key' => 'banner1_image', 'label' => 'Gambar banner 1', 'def' => '/site-assets/cfb2d68082e6365c.png', 'image' => true],
    ['key' => 'banner2_image', 'label' => 'Gambar banner 2', 'def' => '/site-assets/3d2adcf3a0072a68.png', 'image' => true],
    ['key' => 'banner3_image', 'label' => 'Gambar banner 3', 'def' => '/site-assets/33b5dd1015adc1e5.png', 'image' => true],
];

function v(string $key): string {
    $saved = (content('values') ?? [])[$key] ?? '';
    if ($saved !== '') return (string)$saved;
    foreach (VALUE_FIELDS as $f) if ($f['key'] === $key) return $f['def'];
    return '';
}

function devices(): array { return content('pricelist') ?: json_file('pricelist.json'); }
function branches(): array { return content('branches') ?: json_file('branches.json'); }

function wa_link(string $text, ?string $number = null): string {
    return 'https://wa.me/' . (($number ?? '') !== '' ? $number : v('whatsapp')) . '?text=' . rawurlencode($text);
}

const WORDS = [
    'Baterai Genuine' => 'Genuine Battery', 'Kamera Belakang' => 'Rear Camera', 'Kamera Depan' => 'Front Camera',
    'Kaca Kamera' => 'Camera Glass', 'Kerusakan IC' => 'IC Damage', 'Baterai' => 'Battery', 'Kamera' => 'Camera', 'Pilihan' => 'Option',
];

/** Automatic English for labels from the price data. */
function tr_data(string $s): string {
    if ($s === '') return $s;
    if (preg_match('/^Garansi (\d+) Bulan$/u', $s, $m)) return "{$m[1]}-Month Warranty";
    if (preg_match('/^Kualitas (.+)$/u', $s, $m)) return "{$m[1]} Quality";
    if (preg_match('/^Model (.+)$/u', $s, $m)) return "{$m[1]} Model";
    if (str_contains($s, ' / ')) {
        [$dev, $rep] = array_pad(explode(' / ', $s, 2), 2, '');
        $rep = preg_replace_callback('/\b\w+/u', fn($w) => mb_substr($w[0], 0, 1) . mb_strtolower(mb_substr($w[0], 1)), $rep);
        return $dev . ' / ' . mb_strtoupper(tr_data($rep));
    }
    foreach (WORDS as $k => $val) $s = preg_replace('/\b' . preg_quote($k, '/') . '\b/u', $val, $s);
    return $s;
}

/** Pricelist label in the current language, preferring the admin-entered English. */
function d(string $s, ?string $en = null): string {
    return lang() === 'en' ? (($en ?? '') !== '' ? $en : tr_data($s)) : $s;
}

function mobile_nav(string $page): void {
    $links = [['/', t('Beranda'), 'home'], ['/pricelist', 'Pricelist', 'pricelist'], ['/#cabang', t('Cabang'), ''], ['/#testimoni', t('Testimoni'), '']];
    echo '<nav id="site-mobile-menu" style="display:none" class="site-mobile-nav flex flex-col pt-[6px] px-[20px] pb-[18px] min-[761px]:hidden" aria-label="' . e(t('Navigasi ponsel')) . '">';
    foreach ($links as [$href, $label, $key]) {
        echo '<a class="border-b border-[rgb(238,238,238)] py-[12px] text-[12px]" href="' . $href . '"' . ($key === $page ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
}

function page_head(string $title, string $description, string $pageCss): void { ?>
<!DOCTYPE html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="apple-touch-icon" href="/favicon.svg">
<style>@layer imported-inline;</style>
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="/assets/<?= $pageCss ?>">
</head>
<body>
<?php }

function page_foot(): void { ?>
<script src="/assets/site.js" defer></script>
</body>
</html>
<?php }
