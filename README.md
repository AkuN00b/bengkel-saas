# BengkelOS

**EN:** A mobile-friendly workshop SaaS built as one Laravel application. F01 provides the application foundation; F02 adds account authentication. Workshop modules follow as separate tasks.

**ID:** SaaS bengkel yang nyaman digunakan dari HP dalam satu aplikasi Laravel. F01 menyediakan fondasi aplikasi; F02 menambahkan autentikasi akun. Modul bengkel mengikuti sebagai task terpisah.

## Stack

PHP 8.4+, Laravel 13, Blade, Tailwind CSS 4, Alpine.js 3, Vite 8, PostgreSQL, PHPUnit 12.

## Current stage and laptop checks / Tahap sekarang dan pemeriksaan laptop

**ID:** Untuk urutan tes mandiri setelah merge F05, mulai dari [checklist ringkas laptop](docs/MANUAL_TESTING.md#0-start-here-after-f05-merge--mulai-dari-sini-setelah-merge-f05): persiapan, registrasi owner/trial, login/logout, email reset nyata, dan pencatatan hasil.

**EN:** For manual testing after the F05 merge, start with the [laptop checklist](docs/MANUAL_TESTING.md#0-start-here-after-f05-merge--mulai-dari-sini-setelah-merge-f05): setup, owner/trial registration, login/logout, real reset email, and results.

**EN:** F02–F04 are merged into `main`. This change implements F05 — Plans + Subscription + Trial; F06 — Active Workshop + Tenant Isolation remains the next task. Laptop verification and real reset-email delivery are still unconfirmed and have been explicitly deferred by the user. After pulling the relevant version, follow the [manual testing checklist](docs/MANUAL_TESTING.md).

**ID:** F02–F04 sudah masuk `main`. Perubahan ini mengimplementasikan F05 — Plans + Subscription + Trial; F06 — Active Workshop + Tenant Isolation tetap task berikutnya. Verifikasi laptop dan pengiriman email reset nyata masih belum dikonfirmasi dan ditunda atas instruksi user. Setelah pull versi terkait, ikuti [checklist pengujian mandiri](docs/MANUAL_TESTING.md).

## F01 status / Status F01

**EN:** Laravel 13.38.0 is installed. Herd serves the verified BengkelOS home page at http://bengkel-saas.test with HTTP 200 via Nginx. IIS (W3SVC) was stopped temporarily to resolve the port conflict; the original F01 checks (2 PHPUnit tests, Pint, Composer validation/audit, and frontend build) passed. The three standard Laravel migrations are included. The PostgreSQL connection and all three migrations were verified successfully. F01 is complete.

**ID:** Laravel 13.38.0 terpasang. Herd menyajikan halaman BengkelOS yang sudah diverifikasi di http://bengkel-saas.test dengan HTTP 200 melalui Nginx. IIS (W3SVC) dihentikan sementara untuk menyelesaikan konflik port; pemeriksaan awal F01 (2 test PHPUnit, Pint, validasi/audit Composer, dan build frontend) lolos. Tiga migration standar Laravel disertakan. Koneksi PostgreSQL dan ketiga migration telah diverifikasi berhasil. F01 selesai.

## F02 — Authentication / Autentikasi

**EN:** Registration, login, remember-me, POST-only logout, forgot-password, and password reset use Laravel's session guard, hashed password cast, and password broker. `/dashboard` is a minimal authenticated account page. F02 originally registered only users; F05 below extends registration with owner/workshop/trial provisioning. The full UI shell remains a later task. No schema changes or additional packages are required.

**ID:** Registrasi, login, ingat saya, logout hanya melalui POST, lupa kata sandi, dan reset kata sandi memakai session guard, cast hash kata sandi, serta password broker Laravel. `/dashboard` merupakan halaman akun terautentikasi minimal. Pada F02 registrasi awalnya hanya membuat pengguna; F05 di bawah menambahkan provisioning owner/workshop/trial. UI shell lengkap tetap task berikutnya. Tidak ada perubahan schema atau paket tambahan.

**EN:** New account emails are trimmed and lowercased. Passwords require at least 12 characters and at most 72 bytes (bcrypt limit). Login blocks an email/IP pair for 60 seconds after 5 failed attempts; an additional IP limit allows 30 login requests/minute. Registration allows 5 requests/minute/IP. Forgot/reset requests share a 5 requests/minute/IP limit; the broker also throttles reset email delivery for 60 seconds per account. Reset tokens expire after 60 minutes, are stored hashed, and are single-use. Password reset rotates remember tokens; protected routes use `auth.session` to reject sessions carrying an old password hash. Forgot-password responses do not disclose account existence.

**ID:** Email akun baru dibersihkan dari spasi awal/akhir dan dijadikan huruf kecil. Kata sandi minimal 12 karakter dan maksimal 72 byte (batas bcrypt). Login diblokir selama 60 detik untuk pasangan email/IP setelah 5 kegagalan; batas tambahan per IP adalah 30 request login/menit. Registrasi dibatasi 5 request/menit/IP. Request lupa/reset berbagi batas 5 request/menit/IP; broker juga membatasi pengiriman email reset per akun selama 60 detik. Token reset berlaku 60 menit, disimpan sebagai hash, dan hanya dapat digunakan sekali. Reset mengganti remember token; route terlindungi memakai `auth.session` untuk menolak session dengan hash kata sandi lama. Respons lupa kata sandi tidak mengungkap apakah akun terdaftar.

### Email setup / Persiapan email

**EN:** Set `APP_URL` to the actual trusted application URL: reset links use this configured URL rather than the request host. Configure `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` in your untracked `.env`. Use an SMTP sandbox locally; use a real mail provider in production. Do not use the `log` mailer for reset emails because links contain secret tokens. Existing `.env` files are not automatically changed by `.env.example`. SMTP delivery to a real inbox needs a separate environment check; tests fake notifications and verify token/URL generation.

**ID:** Isi `APP_URL` dengan URL aplikasi yang benar dan tepercaya: tautan reset memakai URL konfigurasi ini, bukan host request. Konfigurasikan `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `MAIL_FROM_ADDRESS` di `.env` yang tidak dilacak Git. Gunakan SMTP sandbox untuk lokal dan penyedia email nyata untuk production. Jangan gunakan mailer `log` untuk email reset karena tautannya mengandung token rahasia. File `.env` yang sudah ada tidak otomatis berubah mengikuti `.env.example`. Pengiriman SMTP ke kotak masuk nyata perlu pemeriksaan environment terpisah; tes menggunakan notifikasi simulasi dan memeriksa pembuatan token/URL.

### F02 validation / Validasi F02

**EN:** On an isolated PostgreSQL 17 testing database with PHP 8.4, the suite passes 41 tests / 241 assertions. Pint and the production frontend build pass. Browser checks at desktop and mobile sizes pass registration, logout, login, invalid-credential feedback, and forgot-password submission without JavaScript errors or horizontal overflow. Authentication feature tests cover validation, CSRF, session rotation/logout, throttling, guest restrictions, escaped output, reset-link host safety, invalid/expired/cross-account/reused tokens, and password-reset behavior. Docker was used only as a temporary verification runtime; it is not a project requirement.

**ID:** Pada database PostgreSQL 17 khusus tes dengan PHP 8.4, suite lolos 41 test / 241 assertion. Pint dan build frontend production lolos. Pemeriksaan browser desktop dan HP lolos untuk registrasi, logout, login, pesan kredensial salah, serta pengiriman form lupa kata sandi tanpa error JavaScript atau halaman melebar. Feature test autentikasi mencakup validasi, CSRF, rotasi session/logout, throttling, pembatasan tamu, output yang di-escape, keamanan host tautan reset, token salah/kedaluwarsa/lintas akun/digunakan ulang, serta perilaku reset kata sandi. Docker hanya dipakai sebagai runtime verifikasi sementara; bukan persyaratan proyek.

## F03 — Workshop Tenant

**EN:** Adds the `workshops` table, `Workshop` model, factory, and bidirectional many-to-many relationship definitions through `workshop_user`. Fields: BIGINT ID, name, globally unique slug, nullable phone/address, timezone (default `Asia/Jakarta`), and timestamps. Workshop names may repeat. Slugs must be supplied explicitly; they are identifiers, not authorization proof. No user/workshop ownership column is added.

**ID:** Menambah tabel `workshops`, model `Workshop`, factory, dan definisi relasi banyak-ke-banyak dua arah melalui `workshop_user`. Field: ID BIGINT, nama, slug unik global, telepon/alamat opsional, timezone (default `Asia/Jakarta`), dan timestamps. Nama workshop boleh sama. Slug harus diisi eksplisit; slug merupakan identifier, bukan bukti otorisasi. Tidak ada kolom kepemilikan user/workshop yang ditambahkan.

**EN:** In F03 the relationship definitions were prepared only; F04 below adds the membership table, roles, and persistence tests. Apply the F04 migrations before querying/attaching these relations. Registration in F03 created only a user; F05 below extends it. There are no workshop HTTP endpoints or UI, automatic provisioning, active-workshop resolution, or tenant-isolation middleware in F03. Those remain later tasks; data isolation is not yet a completed foundation capability.

**ID:** Pada F03 relasi baru berupa definisi; F04 di bawah menambah tabel membership, role, dan tes penyimpanannya. Terapkan migration F04 sebelum query/attach relasi tersebut. Registrasi pada F03 hanya membuat user; F05 di bawah memperluasnya. F03 belum menyediakan endpoint/UI workshop, provisioning otomatis, penentuan workshop aktif, atau middleware isolasi tenant. Semuanya tetap task berikutnya; isolasi data belum merupakan kemampuan foundation yang selesai.

**EN:** Apply the new migration with `php artisan migrate` after updating to this version. Existing migrations are unchanged. PHP 8.4 / PostgreSQL 17 validation: 51 tests / 275 assertions pass, including F02 regression tests and 10 workshop tests; Pint passes. The migration is checked for rollback/reapplication without deleting users. No frontend or package changes.

**ID:** Jalankan migration baru melalui `php artisan migrate` setelah memperbarui ke versi ini. Migration lama tidak diubah. Validasi PHP 8.4 / PostgreSQL 17: 51 tes / 275 assertion lolos, termasuk regresi F02 dan 10 tes workshop; Pint lolos. Migration diperiksa untuk rollback/penerapan ulang tanpa menghapus user. Tidak ada perubahan frontend atau paket.

## F04 — Membership + Roles

**EN:** Adds `workshop_user` with a BIGINT ID, required workshop/user foreign keys, required `owner`/`cashier`/`mechanic` role, timestamps, and `UNIQUE(workshop_id, user_id)`. PostgreSQL enforces valid roles and rejects duplicate/orphan memberships. Parent deletion is restricted while memberships exist; detaching removes only the membership. A user may hold different roles in different workshops.

**ID:** Menambah `workshop_user` dengan ID BIGINT, foreign key workshop/user wajib, role `owner`/`cashier`/`mechanic` wajib, timestamps, dan `UNIQUE(workshop_id, user_id)`. PostgreSQL menolak role tidak valid, membership duplikat, serta referensi tanpa user/workshop. Penghapusan induk dibatasi selama membership ada; detach hanya menghapus membership. Satu user dapat memiliki role berbeda pada workshop berbeda.

**EN:** `WorkshopMembership` is the typed pivot model; relationship pivots expose ID/role/timestamps and cast role to `WorkshopRole`. `User::isMemberOf($workshop)` and `hasWorkshopRole($workshop, $role)` query current database membership, including after revocation. Platform `super_admin` uses a separate `users.is_super_admin` boolean (default false, excluded from mass assignment). `isSuperAdmin()` does not bypass membership checks; a workshop owner is not a platform administrator. No public admin-assignment flow or default admin account is added.

**ID:** `WorkshopMembership` merupakan model pivot; pivot relasi memuat ID/role/timestamps dan cast role ke `WorkshopRole`. `User::isMemberOf($workshop)` dan `hasWorkshopRole($workshop, $role)` membaca membership terkini dari database, termasuk setelah pencabutan. `super_admin` platform memakai boolean `users.is_super_admin` terpisah (default false, tidak dapat diisi lewat mass assignment). `isSuperAdmin()` tidak melewati pemeriksaan membership; owner bengkel bukan administrator platform. Tidak ada alur publik pemberian admin atau akun admin bawaan.

**EN:** Run `php artisan migrate` after updating to F04. Two new migrations add the pivot and platform flag; old migrations are unchanged. In F04 registration created only a user; F05 below provisions the owner role for a new workshop, while incoming privilege fields remain ignored. No membership UI/endpoints, dynamic permission builder, active workshop, provisioning, or subscription flows are added. Future sensitive endpoints must enforce policies and membership/resource ownership; these model helpers alone do not provide complete HTTP tenant isolation (F06).

**ID:** Jalankan `php artisan migrate` setelah memperbarui ke F04. Dua migration baru menambah pivot dan flag platform; migration lama tidak diubah. Pada F04 registrasi hanya membuat user; F05 di bawah memberikan role owner pada workshop baru, sementara input hak akses tetap diabaikan. Belum ada UI/endpoint membership, permission builder dinamis, workshop aktif, provisioning, atau subscription. Endpoint sensitif nantinya wajib memeriksa policy serta membership/kepemilikan resource; helper model ini belum menyediakan isolasi tenant HTTP lengkap (F06).

**EN:** PHP 8.4 / PostgreSQL 17: 72 tests / 354 assertions pass, including F02/F03 regressions, real relationship persistence, role boundaries/revocation, constraints, migration reversal, and registration escalation attempts. Upgrade from F03 preserves test users/workshops, defaults existing users to non-admin, and grants no memberships. Pint and Composer validation pass. No frontend/dependency changes. Laptop and real SMTP checks remain pending.

**ID:** PHP 8.4 / PostgreSQL 17: 72 tes / 354 assertion lolos, termasuk regresi F02/F03, penyimpanan relasi nyata, batas/pencabutan role, constraint, pembalikan migration, dan percobaan eskalasi lewat registrasi. Upgrade dari F03 mempertahankan user/workshop uji, memberi default non-admin pada user lama, dan tidak memberikan membership otomatis. Pint serta validasi Composer lolos. Tidak ada perubahan frontend/dependency. Pemeriksaan laptop dan SMTP nyata tetap belum dilakukan.

## F05 — Plans + Subscription + Trial

**EN:** Registration now requires `workshop_name`. `RegisterOwner` creates a user, a new workshop with a server-generated unique slug, its owner membership, and a free 14-day trial subscription in one database transaction. If any creation fails, every new record rolls back. Only after success are the registration event, login, session regeneration, and redirect to the minimal account page performed. Client-supplied workshop IDs, slug, plan, role, admin flag, status, or dates cannot change provisioning. Active-workshop session context is deferred to F06.

**ID:** Registrasi sekarang mewajibkan `workshop_name`. `RegisterOwner` membuat user, workshop baru dengan slug unik dari server, membership owner, dan subscription trial gratis 14 hari dalam satu transaksi database. Jika tahap mana pun gagal, semua record baru dibatalkan. Event registrasi, login, regenerasi session, dan pengalihan ke halaman akun minimal baru dijalankan setelah berhasil. Workshop ID, slug, paket, role, flag admin, status, atau tanggal dari client tidak dapat mengubah provisioning. Konteks workshop aktif tetap F06.

**EN:** Two new migrations create `plans` and `subscriptions`, including the initial catalog: `trial` is active at Rp0; `starter`/`pro` are inactive with unknown (`NULL`) prices because paid pricing is still a product hypothesis. No separate seeding command is needed. Monetary prices are integer Rupiah; null user limits mean unconfigured, with no quota enforcement added. Registration refuses an unavailable/non-free trial rather than switching to a paid plan.

**ID:** Dua migration baru membuat `plans` dan `subscriptions`, termasuk katalog awal: `trial` aktif dengan Rp0; `starter`/`pro` belum aktif dengan harga belum ditentukan (`NULL`) karena harga berbayar masih hipotesis produk. Tidak perlu perintah seeding terpisah. Harga memakai integer Rupiah; limit user null berarti belum dikonfigurasi dan belum ada penerapan kuota. Registrasi menolak trial yang tidak tersedia/tidak gratis, bukan mengganti ke paket berbayar.

**EN:** Subscription timestamps are UTC instants stored with timezone. Trial start is recorded at second precision; both trial/end deadlines are 14 days later. `isTrialing()` checks status and the time window, and returns false exactly at expiry. It does not update status automatically or block login. Status values are `trialing`, `active`, `expired`, `cancelled`. A partial unique index allows only one stored trialing/active subscription per workshop, while retaining expired/cancelled history; future lifecycle handling must transition status explicitly. Foreign keys restrict deleting referenced plans/workshops. No billing, gateway, renewal/cancellation endpoint, plan selection, or full subscription access middleware is introduced.

**ID:** Timestamp subscription berupa waktu UTC yang disimpan dengan timezone. Awal trial dicatat dengan presisi detik; batas trial/akhir sama-sama 14 hari kemudian. `isTrialing()` memeriksa status serta rentang waktu dan bernilai false tepat pada batas kedaluwarsa. Helper ini tidak mengubah status otomatis atau memblokir login. Status: `trialing`, `active`, `expired`, `cancelled`. Index unik parsial membatasi satu subscription berstatus trialing/active per workshop, sambil mempertahankan riwayat expired/cancelled; lifecycle berikutnya harus mengubah status secara eksplisit. Foreign key membatasi penghapusan paket/workshop yang dirujuk. Tidak ada billing, gateway, endpoint perpanjangan/pembatalan, pemilihan paket, atau middleware akses subscription lengkap.

**EN:** After pulling a version containing F05, run `php artisan migrate` and `npm run build`. Existing users, workshops, and memberships remain unchanged; the upgrade does not retroactively grant trials or create workshops for old accounts. Test the new flow with a new test registration. F05 validation: 100 tests / 468 assertions on PHP 8.4 / PostgreSQL 17, Pint, Composer validation, Blade compilation, and production frontend build pass. Tests include rollback at each provisioning stage, privilege tampering, trial boundaries, database integrity, and F02–F04 regressions. Desktop/mobile browser registration and logout pass without JavaScript errors; database checks confirm both complete owner trials. Laptop and real SMTP checks remain pending.

**ID:** Setelah pull versi yang memuat F05, jalankan `php artisan migrate` dan `npm run build`. User, workshop, dan membership lama tetap; upgrade tidak memberikan trial atau membuat workshop untuk akun lama secara retroaktif. Uji alur baru dengan registrasi akun uji baru. Validasi F05: 100 tes / 468 assertion pada PHP 8.4 / PostgreSQL 17, Pint, validasi Composer, kompilasi Blade, dan build frontend production lolos. Tes mencakup rollback pada tiap tahap provisioning, manipulasi hak akses, batas trial, integritas database, serta regresi F02–F04. Registrasi dan logout lewat browser desktop/HP lolos tanpa error JavaScript; pemeriksaan database membuktikan kedua trial owner lengkap. Pemeriksaan laptop dan SMTP nyata tetap tertunda.

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
