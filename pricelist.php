<?php
require __DIR__ . '/lib.php';
$device = null;
$repair = null;
foreach (devices() as $dv) if ($dv['slug'] === ($_GET['perangkat'] ?? '')) $device = $dv;
if ($device) foreach ($device['repairs'] as $r) if ($r['name'] === ($_GET['perbaikan'] ?? '')) $repair = $r;
page_head('Pricelist Servis — iCare Service', 'Cek harga servis iPhone, iPad, MacBook, iMac, dan Apple Watch di iCare Service Denpasar.', 'pricelist.css');
include __DIR__ . '/partials/pricelist.php';
page_foot();
