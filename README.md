# BengkelOS

**EN:** A mobile-friendly workshop SaaS built as one Laravel application. This bootstrap covers F01 only; authentication and workshop modules are separate tasks.

**ID:** SaaS bengkel yang nyaman digunakan dari HP dalam satu aplikasi Laravel. Bootstrap ini hanya mencakup F01; autentikasi dan modul bengkel merupakan task terpisah.

## Stack

PHP 8.4+, Laravel 13, Blade, Tailwind CSS 4, Alpine.js 3, Vite 8, PostgreSQL, PHPUnit 12.

## F01 status / Status F01

**EN:** Laravel 13.38.0 is installed. Herd serves the verified BengkelOS home page at http://bengkel-saas.test with HTTP 200 via Nginx. IIS (W3SVC) was stopped temporarily to resolve the port conflict; PHPUnit (2 tests), Pint, Composer validation/audit, and the frontend build pass. The three standard Laravel migrations are included. The PostgreSQL connection and all three migrations have been verified successfully. F01 is complete. No authentication or workshop modules have been implemented.

**ID:** Laravel 13.38.0 terpasang. Herd menyajikan halaman BengkelOS yang sudah diverifikasi di http://bengkel-saas.test dengan HTTP 200 melalui Nginx. IIS (W3SVC) dihentikan sementara untuk menyelesaikan konflik port; PHPUnit (2 test), Pint, validasi/audit Composer, dan build frontend lolos. Tiga migration standar Laravel disertakan. Koneksi PostgreSQL dan ketiga migration telah diverifikasi berhasil. F01 selesai. Autentikasi dan modul bengkel belum diimplementasikan.

## Local setup / Persiapan lokal

1. Run / Jalankan `composer install` and / dan `npm install`.
2. Copy / Salin `.env.example` to / ke `.env`.
3. Create a dedicated PostgreSQL database and role, then fill in `DB_*` in `.env`. Never commit credentials. / Buat database dan role PostgreSQL khusus, lalu isi `DB_*` di `.env`. Jangan commit kredensial.
4. Run / Jalankan `php artisan key:generate` and / dan `php artisan migrate`.
5. From this folder, run `herd link bengkel-saas`, then open `http://bengkel-saas.test`. / Dari folder ini, jalankan `herd link bengkel-saas`, lalu buka `http://bengkel-saas.test`.
6. Run `npm run dev` during frontend development, or `npm run build` for compiled assets. / Jalankan `npm run dev` selama pengembangan frontend, atau `npm run build` untuk hasil kompilasi.

**EN:** If Herd commands are absent from this terminal's PATH on Windows, add the following directories to the current terminal session (no permanent system change):

**ID:** Jika perintah Herd belum terdeteksi di PATH terminal Windows, tambahkan direktori berikut ke sesi terminal saat ini (tanpa perubahan sistem permanen):

```powershell
$herdBinPath = Join-Path $env:USERPROFILE '.config\herd\bin'
$env:PATH = "$herdBinPath\php84;$herdBinPath;$env:PATH"
php artisan --version
# Use npm.cmd if PowerShell blocks npm.ps1.
# Gunakan npm.cmd jika PowerShell memblokir npm.ps1.
npm.cmd run build
```

## Verification / Verifikasi

**EN:** For a fresh local PostgreSQL installation, run the following command from the project root. It prompts for the `postgres` administrator password, then a new application password. The script creates a role without administrator privileges and separate application/testing databases. Run only once; existing names cause it to stop. Put the application password in `.env` as `DB_PASSWORD`.

**ID:** Untuk instalasi PostgreSQL lokal baru, jalankan perintah berikut dari root project. Perintah meminta password administrator `postgres`, lalu password baru aplikasi. Script membuat role tanpa hak administrator dan database aplikasi/testing terpisah. Jalankan sekali saja; nama yang sudah ada membuatnya berhenti. Isi password aplikasi di `.env` sebagai `DB_PASSWORD`.

```sh
psql -X -h 127.0.0.1 -U postgres -d postgres -f database/setup-local.sql
```

```sh
php artisan --version
php artisan migrate
php artisan test
npm run build
```

**EN:** Tests use PHPUnit with array-backed sessions/cache and synchronous queues. The default test database is `bengkel_saas_testing` on PostgreSQL. Create a separate testing database and configure `.env.testing` before adding database-dependent tests; never point tests at production or persistent application data.

**ID:** Test menggunakan PHPUnit, session/cache array, dan queue sinkron. Database test default adalah `bengkel_saas_testing` di PostgreSQL. Buat database test terpisah dan konfigurasi `.env.testing` sebelum menambahkan test yang memerlukan database; jangan arahkan test ke production atau data aplikasi persisten.

## Project documentation / Dokumentasi project

- [Agent instructions / Instruksi agent](AGENTS.md)
- [Product / Produk](docs/PRODUCT.md)
- [Architecture / Arsitektur](docs/ARCHITECTURE.md)
- [Database](docs/DATABASE.md)
- [Roadmap](docs/ROADMAP.md)
