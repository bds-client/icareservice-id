<?php
/** Device picker, repair picker and price table. Expects $device and $repair from pricelist.php. */
$IMAGES = ['iphone' => '/site-assets/cfb2d68082e6365c.png', 'ipad' => '/site-assets/04ccb7b13a01f8f7.png', 'macbook' => '/site-assets/3d2adcf3a0072a68.png', 'imac' => '/site-assets/b88b5086b2cbd069.jpg', 'watch' => '/site-assets/a1c512d164fb349b.png'];
$check = fn(int $s = 13, string $c = '') => '<svg class="shrink-0 ' . $c . '" xmlns="http://www.w3.org/2000/svg" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>';
$arrow = fn(int $s = 15) => '<svg class="shrink-0" xmlns="http://www.w3.org/2000/svg" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg>';
$fileSearch = fn(int $s) => '<svg class="shrink-0" xmlns="http://www.w3.org/2000/svg" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><circle cx="11.5" cy="14.5" r="2.5"></circle><path d="M13.3 16.3 15 18"></path></svg>';
$heading = fn(string $n, string $id, string $title) => '<div class="price-step-heading items-center flex"><span class="grid text-[8px] place-items-center [@media(max-width:760px)]:text-[8px]">' . $n . '</span><h2 class="font-bold tracking-[-0.3px]" id="' . $id . '">' . e($title) . '</h2>';
?>
<section class="price-device-section" aria-labelledby="price-devices-title">
  <p class="price-note items-center inline-flex font-bold"><?= $check(12) ?> <?= e(t('Harga sudah termasuk pemasangan')) ?></p>
  <?= $heading('01', 'price-devices-title', t('Pilih perangkat')) ?><?php if ($device): ?><span class="selection-summary"><?= $check() ?> <?= e($device['name']) ?></span><?php endif ?></div>
  <div class="price-device-grid grid-cols-[repeat(5,1fr)] [@media(max-width:760px)]:mx-[-2px] [@media(max-width:760px)]:overflow-x-auto [@media(max-width:760px)]:pt-[2px] [@media(max-width:760px)]:pr-[2px] [@media(max-width:760px)]:pb-[8px] [@media(max-width:760px)]:pl-[2px] [@media(max-width:760px)]:[scrollbar-width:thin]">
    <?php foreach (devices() as $dv): $on = $device && $device['slug'] === $dv['slug']; ?>
    <button class="price-device items-center flex relative [-webkit-tap-highlight-color:transparent] [&amp;:disabled]:opacity-[0.55]<?= $on ? ' is-selected' : '' ?>" type="button" aria-pressed="<?= $on ? 'true' : 'false' ?>" data-href="/pricelist?perangkat=<?= rawurlencode($dv['slug']) ?>"><img class="block max-w-full mix-blend-normal object-contain w-full [@media(max-width:760px)]:object-contain [@media(max-width:760px)]:w-full" src="<?= e($IMAGES[$dv['slug']] ?? '/site-assets/cfb2d68082e6365c.png') ?>" alt="" width="1254" height="1254"/><span class="font-bold"><?= e($dv['name']) ?></span><?= $on ? $check(13, 'selected-device-check') : '' ?></button>
    <?php endforeach ?>
  </div>
</section>
<?php if (!$device): ?>
<section class="price-repair-section waiting-for-device" aria-labelledby="price-repairs-title">
  <?= $heading('02', 'price-repairs-title', t('Pilih jenis perbaikan')) ?></div>
  <div class="price-instruction items-center flex text-[11px] [@media(max-width:760px)]:text-[11px] [@media(max-width:760px)]:leading-[1.8]"><?= $arrow(21) ?><p><?= e(t('Pilih perangkat di atas untuk melihat jenis perbaikannya.')) ?></p></div>
</section>
<?php else: ?>
<section class="price-repair-section" aria-labelledby="price-repairs-title">
  <?= $heading('02', 'price-repairs-title', t('Pilih jenis perbaikan')) ?></div>
  <?php if ($device['repairs']): ?>
  <div class="repair-options">
    <?php foreach ($device['repairs'] as $r): $sel = $repair && $repair['name'] === $r['name']; ?>
    <button type="button" class="<?= $sel ? 'is-selected' : '' ?>" aria-pressed="<?= $sel ? 'true' : 'false' ?>" data-href="/pricelist?perangkat=<?= rawurlencode($device['slug']) ?>&amp;perbaikan=<?= rawurlencode($r['name']) ?>"><?= e(d($r['name'], $r['name_en'] ?? null)) ?><?= $sel ? $check(12) : '' ?></button>
    <?php endforeach ?>
  </div>
  <?php else: ?>
  <div class="price-empty">
    <?= $fileSearch(35) ?>
    <h3><?= e(L("Pricelist {$device['name']} belum tersedia.", "{$device['name']} pricelist is not available yet.")) ?></h3>
    <p><?= e(t('Silakan hubungi tim iCare untuk menanyakan ketersediaan layanan dan estimasi harga.')) ?></p>
    <a class="site-button" href="<?= e(wa_link("Halo iCare Service, saya ingin menanyakan ketersediaan servis dan harga untuk {$device['name']}.")) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('Tanyakan ke tim iCare')) ?> <?= $arrow() ?></a>
  </div>
  <?php endif ?>
</section>
<?php if ($device['repairs'] && !$repair): ?>
<div class="table-placeholder">
  <?= $fileSearch(32) ?>
  <h3><?= e(t('Harga muncul setelah Anda memilih perbaikan.')) ?></h3>
  <p><?= e(t('Misalnya LCD, Baterai, atau Kamera.')) ?></p>
</div>
<?php elseif ($repair): $total = count($repair['rows']); ?>
<section class="price-results" aria-labelledby="price-results-title" id="hasil">
  <div class="price-results-heading">
    <div>
      <span class="site-eyebrow"><?= e(d($repair['eyebrow'] ?? '', $repair['eyebrow_en'] ?? null)) ?></span>
      <h2 id="price-results-title"><?= e(d($repair['title'] ?? '', $repair['title_en'] ?? null)) ?></h2>
    </div>
    <div class="price-search">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg>
      <input id="price-search" aria-label="<?= e(L("Cari model {$device['name']}", "Search {$device['name']} model")) ?>" placeholder="<?= e(L("Cari model {$device['name']}…", "Search {$device['name']} model…")) ?>" autocomplete="off">
    </div>
  </div>
  <div class="table-meta">
    <p role="status" id="price-count" data-total="<?= $total ?>" data-template="<?= e(L('{n} dari {total} model', '{n} of {total} models')) ?>"><?= e(L("$total dari $total model", "$total of $total models")) ?></p>
    <button type="button" id="price-reset">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path><path d="M8 16H3v5"></path></svg><?= e(t('Perbarui harga')) ?>
    </button>
  </div>
  <div class="price-table-scroll" role="region" aria-label="<?= e(L("Tabel harga {$repair['title']}, dapat digeser ke samping", d($repair['title'] ?? '', $repair['title_en'] ?? null) . ' price table, scrollable sideways')) ?>" tabindex="0">
    <table class="public-price-table">
      <thead><tr>
        <th scope="col"><?= e(d($repair['first'] ?? '', $repair['first_en'] ?? null)) ?></th>
        <?php foreach ($repair['cols'] as $c): ?><th scope="col"><strong><?= e(d($c['label'], $c['label_en'] ?? null)) ?></strong><?php if (($c['warranty'] ?? '') !== ''): ?><span><?= e(d($c['warranty'], $c['warranty_en'] ?? null)) ?></span><?php endif ?></th><?php endforeach ?>
        <th scope="col"><?= e(t('Tanya')) ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($repair['rows'] as $row): ?>
        <tr data-model="<?= e(mb_strtolower($row['model'])) ?>">
          <th scope="row"><?= e($row['model']) ?></th>
          <?php foreach ($row['prices'] as $p): ?><td><?= ($p === null || $p === '') ? '<span class="empty-price" title="' . e(t('Harga belum tercantum')) . '">—</span>' : e($p) ?></td><?php endforeach ?>
          <td><a class="row-consult" href="<?= e(wa_link("Halo iCare Service, saya ingin bertanya tentang {$repair['name']} untuk {$row['model']}.")) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(L("Konsultasikan {$repair['name']} {$row['model']}", 'Consult about ' . d($repair['name'], $repair['name_en'] ?? null) . " {$row['model']}")) ?>"><?= $arrow(16) ?></a></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="price-footnotes">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
    <p><?= e(t('Harga dalam rupiah. Tanda “—” berarti harga belum tercantum. Diagnosis, ketersediaan sparepart, ketentuan garansi, dan biaya akhir dikonfirmasi sebelum pengerjaan.')) ?></p>
  </div>
</section>
<?php endif ?>
<?php endif ?>
