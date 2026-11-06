# iCare Service — versi PHP & MySQL

Butuh: hosting dengan PHP 8.0+ dan MySQL/MariaDB (cPanel, Hostinger, Niagahoster, dll.), server Apache (untuk file .htaccess).

## Cara pasang
1. Upload seluruh isi folder ini ke folder utama domain (biasanya `public_html`). Situs harus berada di akar domain, bukan di subfolder.
2. Buat database MySQL beserta user-nya di panel hosting.
3. Salin `config.sample.php` menjadi `config.php`, lalu isi nama database, user, dan password.
4. Buka `https://domain-anda/setup.php` — tabel dibuat otomatis, lalu buat akun admin pertama.
5. Hapus `setup.php` dari hosting.
6. Masuk di `https://domain-anda/admin`.

Atau impor `install.sql` lewat phpMyAdmin, lalu tetap pakai setup.php untuk membuat akun admin.

## Isi
- `index.php` — Beranda, `pricelist.php` — Pricelist (bisa dibuka sebagai /pricelist).
- `admin/` — halaman admin: Teks halaman (ID & EN), Kontak & gambar, Cabang, Pricelist, Staf & akun.
- `data/` — isi awal (teks, harga, cabang). Dipakai selama belum ada perubahan dari admin.
- `uploads/` — gambar banner yang diupload admin. Folder ini harus bisa ditulisi (izin 755 atau 775).

Bahasa pilihan pengunjung (ID/EN) disimpan di cookie.

## Naikkan ke GitHub sebagai repo sendiri
1. Buka github.com → New repository → nama `icareservice-id` → Public → Create repository.
2. Ekstrak ZIP paket ini, lalu di halaman repo baru pilih "uploading an existing file" dan seret seluruh isi folder (termasuk file yang dimulai dengan titik: `.htaccess`, `.gitignore`).
3. Jangan upload `config.php` — file itu berisi data database hosting Anda. Yang ikut adalah `config.sample.php`.
4. Setelah itu repo bisa di-clone dan di-push seperti biasa; `.gitignore` sudah menjaga `config.php` dan isi `uploads/` tidak ikut terkirim.
