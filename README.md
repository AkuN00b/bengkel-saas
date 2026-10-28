# BengkelOS

**EN:** A mobile-friendly workshop SaaS built as one Laravel application. F01 provides the application foundation; F02 adds account authentication. Workshop modules follow as separate tasks.

**ID:** SaaS bengkel yang nyaman digunakan dari HP dalam satu aplikasi Laravel. F01 menyediakan fondasi aplikasi; F02 menambahkan autentikasi akun. Modul bengkel mengikuti sebagai task terpisah.

## Stack

PHP 8.4+, Laravel 13, Blade, Tailwind CSS 4, Alpine.js 3, Vite 8, PostgreSQL, PHPUnit 12.

## Current stage and laptop checks / Tahap sekarang dan pemeriksaan laptop

**EN:** F02 — Authentication is merged into `main`; F03 — Workshop Tenant has not started. Laptop verification and real reset-email delivery are still unconfirmed. After pulling, follow the [manual testing checklist](docs/MANUAL_TESTING.md), which distinguishes completed cloud checks from outstanding local checks and provides a results template.

**ID:** F02 — Authentication sudah masuk `main`; F03 — Workshop Tenant belum dimulai. Verifikasi laptop dan pengiriman email reset nyata masih belum dikonfirmasi. Setelah pull, ikuti [checklist pengujian mandiri](docs/MANUAL_TESTING.md), yang mencatat hasil tes cloud, pemeriksaan lokal yang masih perlu dilakukan, dan template hasil.

## F01 status / Status F01

**EN:** Laravel 13.38.0 is installed. Herd serves the verified BengkelOS home page at http://bengkel-saas.test with HTTP 200 via Nginx. IIS (W3SVC) was stopped temporarily to resolve the port conflict; the original F01 checks (2 PHPUnit tests, Pint, Composer validation/audit, and frontend build) passed. The three standard Laravel migrations are included. The PostgreSQL connection and all three migrations were verified successfully. F01 is complete.

**ID:** Laravel 13.38.0 terpasang. Herd menyajikan halaman BengkelOS yang sudah diverifikasi di http://bengkel-saas.test dengan HTTP 200 melalui Nginx. IIS (W3SVC) dihentikan sementara untuk menyelesaikan konflik port; pemeriksaan awal F01 (2 test PHPUnit, Pint, validasi/audit Composer, dan build frontend) lolos. Tiga migration standar Laravel disertakan. Koneksi PostgreSQL dan ketiga migration telah diverifikasi berhasil. F01 selesai.

## F02 — Authentication / Autentikasi

**EN:** Registration, login, remember-me, POST-only logout, forgot-password, and password reset use Laravel's session guard, hashed password cast, and password broker. `/dashboard` is a minimal authenticated account page. Registration creates only a user; workshops, memberships, roles, trial provisioning, and the full UI shell remain later roadmap tasks. No schema changes or additional packages are required.

**ID:** Registrasi, login, ingat saya, logout hanya melalui POST, lupa kata sandi, dan reset kata sandi memakai session guard, cast hash kata sandi, serta password broker Laravel. `/dashboard` merupakan halaman akun terautentikasi minimal. Registrasi hanya membuat pengguna; workshop, membership, role, provisioning trial, dan UI shell lengkap tetap task roadmap berikutnya. Tidak ada perubahan schema atau paket tambahan.

**EN:** New account emails are trimmed and lowercased. Passwords require at least 12 characters and at most 72 bytes (bcrypt limit). Login blocks an email/IP pair for 60 seconds after 5 failed attempts; an additional IP limit allows 30 login requests/minute. Registration allows 5 requests/minute/IP. Forgot/reset requests share a 5 requests/minute/IP limit; the broker also throttles reset email delivery for 60 seconds per account. Reset tokens expire after 60 minutes, are stored hashed, and are single-use. Password reset rotates remember tokens; protected routes use `auth.session` to reject sessions carrying an old password hash. Forgot-password responses do not disclose account existence.

**ID:** Email akun baru dibersihkan dari spasi awal/akhir dan dijadikan huruf kecil. Kata sandi minimal 12 karakter dan maksimal 72 byte (batas bcrypt). Login diblokir selama 60 detik untuk pasangan email/IP setelah 5 kegagalan; batas tambahan per IP adalah 30 request login/menit. Registrasi dibatasi 5 request/menit/IP. Request lupa/reset berbagi batas 5 request/menit/IP; broker juga membatasi pengiriman email reset per akun selama 60 detik. Token reset berlaku 60 menit, disimpan sebagai hash, dan hanya dapat digunakan sekali. Reset mengganti remember token; route terlindungi memakai `auth.session` untuk menolak session dengan hash kata sandi lama. Respons lupa kata sandi tidak mengungkap apakah akun terdaftar.

### Email setup / Persiapan email

**EN:** Set `APP_URL` to the actual trusted application URL: reset links use this configured URL rather than the request host. Configure `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` in your untracked `.env`. Use an SMTP sandbox locally; use a real mail provider in production. Do not use the `log` mailer for reset emails because links contain secret tokens. Existing `.env` files are not automatically changed by `.env.example`. SMTP delivery to a real inbox needs a separate environment check; tests fake notifications and verify token/URL generation.

**ID:** Isi `APP_URL` dengan URL aplikasi yang benar dan tepercaya: tautan reset memakai URL konfigurasi ini, bukan host request. Konfigurasikan `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `MAIL_FROM_ADDRESS` di `.env` yang tidak dilacak Git. Gunakan SMTP sandbox untuk lokal dan penyedia email nyata untuk production. Jangan gunakan mailer `log` untuk email reset karena tautannya mengandung token rahasia. File `.env` yang sudah ada tidak otomatis berubah mengikuti `.env.example`. Pengiriman SMTP ke kotak masuk nyata perlu pemeriksaan environment terpisah; tes menggunakan notifikasi simulasi dan memeriksa pembuatan token/URL.

### F02 validation / Validasi F02

**EN:** On an isolated PostgreSQL 17 testing database with PHP 8.4, the suite passes 41 tests / 241 assertions. Pint and the production frontend build pass. Browser checks at desktop and mobile sizes pass registration, logout, login, invalid-credential feedback, and forgot-password submission without JavaScript errors or horizontal overflow. Authentication feature tests cover validation, CSRF, session rotation/logout, throttling, guest restrictions, escaped output, reset-link host safety, invalid/expired/cross-account/reused tokens, and password-reset behavior. Docker was used only as a temporary verification runtime; it is not a project requirement.

**ID:** Pada database PostgreSQL 17 khusus tes dengan PHP 8.4, suite lolos 41 test / 241 assertion. Pint dan build frontend production lolos. Pemeriksaan browser desktop dan HP lolos untuk registrasi, logout, login, pesan kredensial salah, serta pengiriman form lupa kata sandi tanpa error JavaScript atau halaman melebar. Feature test autentikasi mencakup validasi, CSRF, rotasi session/logout, throttling, pembatasan tamu, output yang di-escape, keamanan host tautan reset, token salah/kedaluwarsa/lintas akun/digunakan ulang, serta perilaku reset kata sandi. Docker hanya dipakai sebagai runtime verifikasi sementara; bukan persyaratan proyek.

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

**EN:** Tests use PHPUnit with array-backed sessions/cache and synchronous queues. The default test database is `bengkel_saas_testing` on PostgreSQL. Create a separate testing database and configure `.env.testing` (including `APP_KEY` and `DB_*`) before running authentication tests. They use `RefreshDatabase`, which may rebuild the testing schema. Never point tests at production or persistent application data. Feature tests disable Vite lookup; validate assets separately with `npm run build`.

**ID:** Test menggunakan PHPUnit, session/cache array, dan queue sinkron. Database test default adalah `bengkel_saas_testing` di PostgreSQL. Buat database test terpisah dan konfigurasi `.env.testing` (termasuk `APP_KEY` dan `DB_*`) sebelum menjalankan tes autentikasi. Tes memakai `RefreshDatabase`, yang dapat membangun ulang schema tes. Jangan arahkan test ke production atau data aplikasi persisten. Feature test menonaktifkan pencarian Vite; validasi aset terpisah melalui `npm run build`.

## Project documentation / Dokumentasi project

- [Agent instructions / Instruksi agent](AGENTS.md)
- [Product / Produk](docs/PRODUCT.md)
- [Architecture / Arsitektur](docs/ARCHITECTURE.md)
- [Database](docs/DATABASE.md)
- [Roadmap](docs/ROADMAP.md)
- [Manual testing / Pengujian mandiri](docs/MANUAL_TESTING.md)
