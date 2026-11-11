# BengkelOS

**EN:** A mobile-friendly workshop SaaS built as one Laravel application. Foundation, customers/vehicles, work orders/mechanics, inventory, invoices/payments, and Sprint 5 dashboards/reports are implemented.

**ID:** SaaS bengkel yang nyaman digunakan dari HP dalam satu aplikasi Laravel. Foundation, pelanggan/kendaraan, perintah kerja/mekanik, stok, invoice/pembayaran, serta dashboard/laporan Sprint 5 sudah tersedia.

## Stack

PHP 8.4+, Laravel 13, Blade, Tailwind CSS 4, Alpine.js 3, Vite 8, PostgreSQL, PHPUnit 12.

## Current stage and laptop checks / Tahap sekarang dan pemeriksaan laptop

**ID / EN — Validasi Sprint 5 / Sprint 5 validation:** **419 tests / 2.732 assertions**, 3 JavaScript tests, Pint, Composer strict, Blade, Vite, all-role desktop/mobile browser, CSV and A4/PDF checks passed. Tag: `sprint-5-dashboard-reports-v1`.

**ID — Sprint 5:** D01–D02 dashboard owner/kasir/mekanik, laporan pembayaran/koreksi dan nilai invoice, piutang historis sampai detik, penelusuran invoice, riwayat servis, CSV, serta cetak A4/PDF browser tersedia. Keuangan dan ekspor/cetak laporan khusus owner. Lihat [definisi angka Sprint 5](docs/SPRINT_5_DASHBOARD_REPORTS.md) dan [checklist terbaru](docs/MANUAL_TESTING.md#sprint-5--dashboard-dan-laporan). Manual Sprint 2–5 belum dikonfirmasi. Total **15 migration Ran**, data lama tetap. Tahap berikutnya Alpha: validasi bengkel nyata dan perbaikan operasional; belum dijalankan.

**EN — Sprint 5:** Role dashboards, payment/correction and snapshot invoice-value reports, exact-time historical receivables/invoice tracing, service history, CSV, and browser A4/PDF printing are available. Financial reports and every report export/print action are owner-only. See the linked definitions and latest checklist. **15 migrations ran** with existing data retained. Human Sprint 2–5 acceptance is unconfirmed; Alpha field validation/operational fixes have not started. Older sprint entries below are historical checkpoints.

**ID — Sprint 4:** Draft invoice dari WO, penyesuaian harga beralasan, penerbitan setelah Selesai, pembayaran bertahap, koreksi owner, penggantian invoice, serta cetak A4/PDF browser tersedia. Jumlah barang memakai pemakaian bersih saat sumber disalin; retur sesudah terbit tidak otomatis mengubah tagihan. Lihat [Sprint 4](docs/SPRINT_4_INVOICES_PAYMENTS.md) dan [checklist terbaru](docs/MANUAL_TESTING.md#sprint-4--invoice-dan-pembayaran). Manual Sprint 2/3/4 tetap belum dikonfirmasi.

**EN — Sprint 4:** Drafts from WOs, reasoned price overrides, completed-work issuance, installments, owner corrections/replacements, and A4/browser-PDF printing are available. Quantities use net consumption at source capture; later returns do not rewrite issued bills. See [Sprint 4](docs/SPRINT_4_INVOICES_PAYMENTS.md) and [latest checklist](docs/MANUAL_TESTING.md#sprint-4--invoice-dan-pembayaran). Manual acceptance remains unconfirmed.

**ID / EN — Validasi / Validation:** 382 tests / 2.467 assertions, 3 JavaScript tests, Pint, Composer validation, Blade, Vite build, desktop/mobile browser and A4/PDF checks passed. 14 migrations Ran. Tag: `sprint-4-invoices-payments-v1`.

**ID — Sprint 3:** Katalog jasa/spare part, baris WO, restock, koreksi owner, konfirmasi pemakaian saat Dikerjakan, dan retur parsial sudah tersedia. Harga WO dapat disesuaikan tanpa mengubah katalog. Stok tidak negatif dan transaksi duplikat tidak menggandakan ledger. Retur hanya mengubah stok; belum ada invoice/pembayaran. Lihat [Sprint 3](docs/SPRINT_3_SERVICES_INVENTORY.md) dan [checklist terbaru](docs/MANUAL_TESTING.md#sprint-3--jasa-spare-part-dan-stok). Penerimaan manual Sprint 2/3 tetap terpisah dari hasil otomatis.

**EN — Sprint 3:** Service/part catalogs, WO lines, restock, owner adjustments, in-progress consumption, and partial returns are available. WO prices can differ from catalogs. Stock cannot be negative; replayed transactions cannot duplicate movements. Returns affect inventory only; invoices/payments are not implemented. See [Sprint 3](docs/SPRINT_3_SERVICES_INVENTORY.md) and the [latest checklist](docs/MANUAL_TESTING.md#sprint-3--jasa-spare-part-dan-stok). Manual Sprint 2/3 acceptance remains separate from automation.

**ID — Validasi Sprint 3:** 358 tes / 2.151 assertion, 3 tes JavaScript, Pint, Composer validation, Blade, build Vite, dan browser desktop/HP lolos. Total 13 migration Ran. Tag: `sprint-3-services-inventory-v1`.

**EN — Sprint 3 validation:** 358 tests / 2,151 assertions, 3 JavaScript tests, Pint, Composer validation, Blade compilation, Vite build, and desktop/mobile browser checks passed. All 13 migrations ran. Tag: `sprint-3-services-inventory-v1`.

**ID — Sprint 2:** Cari kendaraan → buat WO → Antre → tugaskan mekanik → Dikerjakan → Selesai/batal. Owner mengelola akun mekanik dan pembatalan; kasir mengelola WO aktif; mekanik memproses tugas sendiri. Selesai pekerjaan belum berarti lunas. Ikuti [keputusan Sprint 2](docs/SPRINT_2_WORK_ORDERS.md) dan [checklist terbaru](docs/MANUAL_TESTING.md#sprint-2--wo-dan-mekanik). Migration baru mempertahankan data lama. Penerimaan manual user dan SMTP asli tetap terpisah dari tes otomatis.

**EN — Sprint 2:** Find vehicle → create WO → queue → assign → start → complete/cancel. Owners manage mechanic accounts and cancellations; cashiers manage active WOs; mechanics process their own assignments. Completion does not imply payment. See [Sprint 2 decisions](docs/SPRINT_2_WORK_ORDERS.md) and the [latest checklist](docs/MANUAL_TESTING.md#sprint-2--wo-dan-mekanik). New migrations preserve existing data. Manual acceptance and real SMTP delivery remain separate from automation.

**ID — Validasi Sprint 2:** 335 tes / 1.813 assertion, 3 tes JavaScript, Pint, Composer validation, kompilasi Blade, build, dan browser desktop/HP lolos. Total 12 migration Ran. Tag: `sprint-2-work-orders-v1`.

**EN — Sprint 2 validation:** 335 tests / 1,813 assertions, 3 JavaScript tests, Pint, Composer validation, Blade compilation, build, and desktop/mobile browser checks passed. All 12 migrations ran. Tag: `sprint-2-work-orders-v1`.

**ID:** Untuk urutan tes mandiri setelah merge F05, mulai dari [checklist ringkas laptop](docs/MANUAL_TESTING.md#0-start-here-after-f05-merge--mulai-dari-sini-setelah-merge-f05): persiapan, registrasi owner/trial, login/logout, email reset nyata, dan pencatatan hasil.

**EN:** For manual testing after the F05 merge, start with the [laptop checklist](docs/MANUAL_TESTING.md#0-start-here-after-f05-merge--mulai-dari-sini-setelah-merge-f05): setup, owner/trial registration, login/logout, real reset email, and results.

**EN:** F01–F07 are merged into `main`. F08 completes the Foundation code review and isolated validation: 161 tests / 703 assertions pass, with tenant isolation verified for the existing endpoints. See the [Foundation review](docs/FOUNDATION_REVIEW.md) and [F08 laptop checklist](docs/MANUAL_TESTING.md#f08-laptop-follow-up--pemeriksaan-laptop-f08). Earlier manual results are preserved; cloud checks do not establish laptop/SMTP results. Customer/vehicle work (C01 onward) requires a separate instruction.

**ID:** F01–F07 sudah masuk `main`. F08 menyelesaikan review kode dan validasi Foundation di lingkungan terpisah: 161 tes / 703 assertion lolos, dengan isolasi tenant terverifikasi pada endpoint yang sudah tersedia. Lihat [laporan Foundation](docs/FOUNDATION_REVIEW.md) dan [checklist laptop F08](docs/MANUAL_TESTING.md#f08-laptop-follow-up--pemeriksaan-laptop-f08). Hasil manual lama dipertahankan; tes cloud tidak membuktikan hasil laptop/SMTP. Fitur pelanggan/kendaraan (C01 dan seterusnya) memerlukan instruksi terpisah.

**EN:** C01 customer management is implemented and validated: **221 tests / 1,069 assertions** pass across the full suite. Owners/cashiers can add/edit customers; mechanics can view/search; deletion is unavailable. Follow the [C01 laptop checklist](docs/MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01) after pulling, including the new customer migration. [Sprint 1 progress](docs/SPRINT_1_CUSTOMER_VEHICLE.md) records the agreed rules and results. C02/C03 are now implemented in the section below.

**ID:** Manajemen pelanggan C01 sudah diimplementasikan dan divalidasi: **221 tes / 1.069 assertion** lolos pada suite penuh. Owner/kasir dapat menambah/mengedit pelanggan; mekanik melihat/mencari; hapus tidak tersedia. Ikuti [checklist laptop C01](docs/MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01) setelah pull, termasuk migration pelanggan baru. [Progres Sprint 1](docs/SPRINT_1_CUSTOMER_VEHICLE.md) mencatat aturan yang disepakati dan hasilnya. C02/C03 kini tersedia pada bagian di bawah.

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

## F06 — Active Workshop + Tenant Isolation

**EN:** The authenticated dashboard now requires a verified active workshop. `active.workshop` middleware resolves `session.active_workshop_id` through the authenticated user's membership on every request, never from query/body/header tenant IDs or cached relationship data. One membership activates automatically; several require selection. A stale, malformed, foreign, or revoked selection is cleared and denied before rendering tenant data. HTML redirects to `/workshops/select`; JSON requests without a valid context receive 403. Users without memberships (including older accounts) see a clear message and can log out. Platform administrators have no automatic membership bypass.

**ID:** Dashboard terautentikasi sekarang memerlukan workshop aktif yang terverifikasi. Middleware `active.workshop` memeriksa `session.active_workshop_id` melalui membership user pada setiap request, bukan ID tenant dari query/body/header atau relasi yang pernah dimuat. Satu membership aktif otomatis; beberapa membership harus dipilih. Pilihan kedaluwarsa, tidak valid, milik tenant lain, atau sudah dicabut dibersihkan dan ditolak sebelum data tenant dirender. HTML dialihkan ke `/workshops/select`; request JSON tanpa konteks valid mendapat 403. User tanpa membership (termasuk akun lama) melihat pesan yang jelas dan bisa logout. Admin platform tidak otomatis melewati membership.

**EN:** `/workshops/select` lists only the user's memberships, 20 per page. CSRF-protected POST `/workshops/active` rechecks membership, rotates the session ID, and redirects to the dashboard; foreign and missing workshop IDs both return 404. Invalid input returns validation errors without changing the previous context. Login/registration clear prior tenant selection; logout invalidates it. The dashboard shows only the resolved workshop's name and a link to switch workshops. This is the minimal selection flow, not the F07 application shell.

**ID:** `/workshops/select` hanya menampilkan membership user, 20 per halaman. POST `/workshops/active` terlindungi CSRF, memeriksa ulang membership, mengganti ID session, lalu mengarah ke dashboard; ID workshop asing dan tidak ditemukan sama-sama mendapat 404. Input tidak valid mendapat pesan validasi tanpa mengubah konteks sebelumnya. Login/registrasi membersihkan pilihan tenant lama; logout menghapus session. Dashboard hanya menampilkan nama workshop yang terverifikasi dan tautan pergantian workshop. Ini alur pemilihan minimal, bukan UI shell F07.

**EN:** No migrations or dependencies are added. Tenant context lives only in the current request's `activeWorkshop` attribute after middleware verification; future tenant routes must use `auth`, `auth.session`, and `active.workshop`, then query resources through that resolved workshop and enforce action policies. There is no global tenant scope or automatic protection for arbitrary unscoped queries. Operational modules do not exist yet; the proven HTTP isolation covers selection and the workshop context shown on the dashboard, not future customer/order/invoice endpoints. Trial access enforcement and role-management UI are not introduced.

**ID:** Tidak ada migration atau dependency baru. Konteks tenant hanya berada pada attribute request `activeWorkshop` setelah verifikasi middleware; route tenant berikutnya wajib memakai `auth`, `auth.session`, dan `active.workshop`, lalu query resource melalui workshop terverifikasi serta menerapkan policy tindakan. Tidak ada global tenant scope atau perlindungan otomatis untuk query tanpa scope. Modul operasional belum ada; isolasi HTTP yang terbukti mencakup pemilihan dan konteks workshop di dashboard, bukan endpoint pelanggan/order/invoice masa depan. Belum ada enforcement akses trial atau UI pengelolaan role.

**EN:** PHP 8.4 / PostgreSQL 17: 130 tests / 593 assertions pass, including two-way tenant denial, tampered IDs/session, revocation/deletion, zero/one/many memberships, all member roles, platform-admin boundaries, pagination, CSRF, and F02–F05 regressions. Pint, Composer validation, Blade compilation, and frontend build pass. Desktop/mobile browser checks pass zero/one/many memberships, switching, and cross-tenant rejection without JavaScript errors or horizontal overflow. Manual laptop/real SMTP checks remain pending; see the [F06 manual checklist](docs/MANUAL_TESTING.md#f06-laptop-follow-up--pemeriksaan-laptop-f06).

**ID:** PHP 8.4 / PostgreSQL 17: 130 tes / 593 assertion lolos, termasuk penolakan lintas tenant dua arah, manipulasi ID/session, pencabutan/penghapusan, nol/satu/banyak membership, semua role anggota, batas admin platform, pagination, CSRF, serta regresi F02–F05. Pint, validasi Composer, kompilasi Blade, dan build frontend lolos. Browser desktop/HP lolos untuk nol/satu/banyak membership, pergantian, dan penolakan tenant lain tanpa error JavaScript atau halaman melebar. Pemeriksaan laptop/SMTP nyata tetap tertunda; lihat [checklist manual F06](docs/MANUAL_TESTING.md#f06-laptop-follow-up--pemeriksaan-laptop-f06).

## F07 — Application UI Shell

**EN:** Authenticated pages now share a responsive Blade/Tailwind shell: desktop sidebar, sticky active-workshop header, a mobile navigation dialog with native focus handling, user details/logout menu, skip link, and main content. Navigation contains only Ringkasan and workshop selection. Workshop selection and zero-membership accounts use the same shell without assuming an authorized active tenant. Public authentication pages keep their existing layout.

**ID:** Halaman terautentikasi memakai shell Blade/Tailwind responsif: sidebar desktop, header bengkel aktif, dialog navigasi HP dengan pengelolaan fokus native, menu pengguna/logout, skip link, dan konten utama. Navigasi hanya berisi Ringkasan serta pemilihan bengkel. Halaman pemilihan dan akun tanpa membership memakai shell yang sama tanpa mengasumsikan tenant aktif yang sah. Halaman autentikasi publik tetap memakai layout sebelumnya.

**EN:** Dashboard data comes from the F06-verified workshop. It shows the user/workshop, the actual membership role, and the latest subscription ordered by start time then ID. Trial days use the effective earlier trial/end deadline and round a partial remaining day up; exactly at expiry the count is zero. Dates display in the workshop timezone, falling back to UTC for an invalid stored timezone. Future trials/packages, expired/cancelled records, and missing subscriptions have explicit labels. Rendering does not create subscriptions, change persisted status, or add trial access enforcement. The account checklist reflects real login, active-workshop selection, and subscription-record availability only.

**ID:** Data dashboard berasal dari workshop terverifikasi F06. Yang ditampilkan: user/workshop, role membership nyata, serta subscription terbaru menurut waktu mulai lalu ID. Sisa hari trial memakai batas trial/akhir yang lebih awal dan membulatkan sisa sebagian hari ke atas; tepat saat kedaluwarsa jumlahnya nol. Tanggal memakai timezone workshop, dengan fallback UTC untuk timezone tersimpan yang tidak valid. Trial/paket belum dimulai, record expired/cancelled, dan subscription kosong memiliki label jelas. Rendering tidak membuat subscription, mengubah status tersimpan, atau menambah enforcement akses trial. Checklist akun hanya mencerminkan login, pemilihan workshop aktif, dan ketersediaan record subscription yang nyata.

**EN:** No fake business metrics, inactive product-module menus, billing actions, migrations, or dependencies are added. F06 authentication/membership checks remain in place; subscription reads are scoped through the resolved workshop relationship. The prior manual-testing report is preserved, with a separate [F07 laptop checklist](docs/MANUAL_TESTING.md#f07-laptop-follow-up--pemeriksaan-laptop-f07). Update assets with `npm run build` after pulling. F08 remains required before declaring Foundation complete.

**ID:** Tidak ada metrik bisnis palsu, menu modul produk yang belum tersedia, aksi billing, migration, atau dependency baru. Pemeriksaan autentikasi/membership F06 tetap berjalan; subscription dibaca melalui relasi workshop terverifikasi. Laporan pengujian mandiri sebelumnya dipertahankan, dengan [checklist laptop F07](docs/MANUAL_TESTING.md#f07-laptop-follow-up--pemeriksaan-laptop-f07) terpisah. Perbarui aset melalui `npm run build` setelah pull. F08 tetap diperlukan sebelum Foundation dinyatakan selesai.

**EN:** Isolated PHP 8.4 / PostgreSQL 17 validation: 149 tests / 671 assertions pass, including trial time boundaries, labels, timezone fallback, XSS, scoped subscription reads, switching, and F02–F06 regressions. Pint, Composer validation, Blade compilation, and frontend build pass. Desktop/mobile Chromium checks cover navigation, dialog Escape/focus return, user menu/logout, switching, trial/empty states, zero membership, and tenant boundaries without JavaScript errors or horizontal overflow. The browser also checks long names and closing the mobile dialog on desktop resize.

**ID:** Validasi PHP 8.4 / PostgreSQL 17 terpisah: 149 tes / 671 assertion lolos, termasuk batas waktu trial, label, fallback timezone, XSS, pembacaan subscription sesuai tenant, pergantian, serta regresi F02–F06. Pint, validasi Composer, kompilasi Blade, dan build frontend lolos. Chromium desktop/HP memeriksa navigasi, Escape/pengembalian fokus dialog, menu pengguna/logout, pergantian, keadaan trial/kosong, nol membership, dan batas tenant tanpa error JavaScript atau scroll horizontal. Browser juga memeriksa nama panjang serta penutupan dialog HP saat resize ke desktop.

## F08 — Foundation Test & Hardening

**EN:** The default seeder no longer creates a predictable demo login and can be rerun without changing existing accounts. The plan catalog remains supplied by its existing migration; owners register normally. Existing accounts are not deleted. Test startup now rejects configurations other than `APP_ENV=testing`, PostgreSQL `bengkel_saas_testing`, and an empty `DB_URL`, before `RefreshDatabase` runs. New regressions also verify session invalidation when passwords change before the first protected request; Laravel already handles this correctly.

**ID:** Seeder default tidak lagi membuat login demo dengan password bawaan dan dapat diulang tanpa mengubah akun yang sudah ada. Katalog paket tetap berasal dari migration yang tersedia; owner mendaftar melalui aplikasi. Akun lama tidak dihapus. Startup tes kini menolak konfigurasi selain `APP_ENV=testing`, PostgreSQL `bengkel_saas_testing`, dan `DB_URL` kosong sebelum `RefreshDatabase` berjalan. Regresi baru juga memverifikasi pencabutan session ketika password berubah sebelum request terlindungi pertama; Laravel sudah menangani kasus ini dengan benar.

**EN:** Four Symfony packages receive security patches in `composer.lock`, resolving seven reported advisories. Run `composer install` after pulling, then clear configuration and rebuild assets as described in the F08 checklist. No new packages, migrations, or operational modules are added. Full evidence, migration/rollback checks, browser results, and remaining manual checks are in the [Foundation review](docs/FOUNDATION_REVIEW.md).

**ID:** Empat paket Symfony mendapat patch keamanan di `composer.lock`, menutup tujuh advisory yang terdeteksi. Jalankan `composer install` setelah pull, lalu bersihkan konfigurasi dan build aset sesuai checklist F08. Tidak ada paket, migration, atau modul operasional baru. Bukti tes, pemeriksaan migration/rollback, hasil browser, dan pemeriksaan manual tersisa ada di [laporan Foundation](docs/FOUNDATION_REVIEW.md).

## C01 — Customer Management / Manajemen Pelanggan

**EN — Local validation, 2026-11-10:** Windows/Herd PHP 8.4.26 + PostgreSQL 18.6: 221 tests / 1,073 assertions pass. The PostgreSQL connection now explicitly uses UTC, fixing trial round-trip/display behavior when the server defaults to another timezone. Restricted-parent deletion tests accept PostgreSQL's foreign-key/restrict SQLSTATEs and verify all parent/membership rows survive. Existing application row counts were preserved while applying the ninth migration. Dependency installation, build, Pint, Composer validation/audit, Blade compilation, and isolated desktop/mobile Edge checks pass. See the C01 local results in [MANUAL_TESTING.md](docs/MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01). No historical subscription timestamps were rewritten; real SMTP was not retested.

**ID — Validasi lokal, 2026-11-10:** Windows/PHP Herd 8.4.26 + PostgreSQL 18.6: 221 tes / 1.073 assertion lolos. Koneksi PostgreSQL sekarang eksplisit UTC agar penyimpanan/pembacaan dan tampilan trial benar meskipun default server berbeda. Tes pembatasan hapus induk menerima SQLSTATE foreign-key/restrict PostgreSQL dan memastikan record induk/membership tetap ada. Jumlah data aplikasi lama terjaga saat migration kesembilan diterapkan. Instalasi dependency, build, Pint, validasi/audit Composer, kompilasi Blade, dan browser Edge desktop/HP terpisah lolos. Detail ada di [MANUAL_TESTING.md](docs/MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01). Timestamp subscription lama tidak ditulis ulang; SMTP nyata tidak diuji ulang.

**EN:** `/customers` provides a 20-row paginated list, literal case-insensitive name/phone search, detail, create, and edit forms in the Indonesian desktop/mobile shell. Names are required (255 characters); phone is optional (32 characters, digits and common phone punctuation); notes are optional (5,000 characters). Duplicate names/phones are allowed. The active workshop is resolved from current membership. All record lookups are scoped through that workshop; incoming ownership/privilege fields are ignored. Policies enforce owner/cashier writes and mechanic reads on the server. No delete endpoint, soft deletion, vehicle module, or new dependency is added.

**ID:** `/customers` menyediakan daftar 20 baris per halaman, pencarian nama/telepon literal tanpa membedakan huruf besar/kecil, detail, serta form tambah/edit pada shell Indonesia desktop/HP. Nama wajib (255 karakter); telepon opsional (32 karakter, angka dan tanda baca telepon umum); catatan opsional (5.000 karakter). Nama/telepon boleh sama. Bengkel aktif ditentukan dari membership terkini. Seluruh pencarian record dibatasi melalui bengkel itu; field kepemilikan/privilege dari request diabaikan. Policy menerapkan izin tulis owner/kasir dan baca mekanik di server. Tidak ada endpoint hapus, soft delete, modul kendaraan, atau dependency baru.

**EN:** Apply `2026_11_09_000000_create_customers_table.php` with `php artisan migrate` after pulling. It adds a BIGINT customer ID, required workshop FK with restricted deletion, nonblank name, nullable phone/notes, timestamps, and an index on `(workshop_id, name, id)`. Shared migrations are unchanged. Isolated upgrade from F08, customer rollback/reapplication, fresh migrations/seed, the full suite, Pint, Composer validation, Blade compilation, and frontend build pass. Chromium desktop/mobile checks pass create/edit, validation/input preservation, search/pagination, long names, A/B isolation, role restrictions, CSRF, workshop switching, and revoked membership. See the [Sprint 1 validation record](docs/SPRINT_1_CUSTOMER_VEHICLE.md). Target milestone: `c01-customer-management-v1`.

**ID:** Terapkan `2026_11_09_000000_create_customers_table.php` melalui `php artisan migrate` setelah pull. Migration menambah ID pelanggan BIGINT, FK bengkel wajib dengan pembatasan penghapusan, nama tidak kosong, telepon/catatan nullable, timestamps, serta index `(workshop_id, name, id)`. Migration lama tidak diubah. Upgrade dari F08, rollback/penerapan ulang pelanggan, fresh migration/seed pada database terpisah, suite penuh, Pint, validasi Composer, kompilasi Blade, dan build frontend lolos. Tes Chromium desktop/HP lolos untuk tambah/edit, validasi/isian dipertahankan, pencarian/pagination, nama panjang, isolasi A/B, pembatasan role, CSRF, pergantian bengkel, dan membership dicabut. Lihat [catatan validasi Sprint 1](docs/SPRINT_1_CUSTOMER_VEHICLE.md). Target milestone: `c01-customer-management-v1`.

## C02/C03 — Vehicles and plate lookup / Kendaraan dan pencarian pelat

**EN:** The user authorized both steps together on 2026-11-10. Owner/cashier may create/edit vehicles; mechanics may view/search; deletion is unavailable. Vehicles have a required plate and optional customer, brand/model/year/notes. Customer references are tenant-checked in validation and a composite database FK. Uppercasing and removing spaces produces a server-owned plate key unique per workshop; the same plate in different workshops is allowed. Existing migrations are preserved; apply `2026_11_10_000000_create_vehicles_table` with ordinary `php artisan migrate`, never a reset of application data.

**ID:** User mengizinkan kedua langkah sekaligus pada 2026-11-10. Owner/kasir boleh tambah-edit kendaraan; mekanik lihat/cari; hapus tidak tersedia. Pelat wajib, pelanggan/merek/model/tahun/catatan opsional. Referensi pelanggan diperiksa sesuai tenant pada validasi dan FK database gabungan. Huruf besar dan penghapusan spasi menghasilkan kunci pelat dari server yang unik per bengkel; pelat sama lintas bengkel diperbolehkan. Migration lama dipertahankan; terapkan `2026_11_10_000000_create_vehicles_table` melalui `php artisan migrate` biasa, tanpa reset data aplikasi.

**EN:** Dashboard/Vehicle lookup opens an exact normalized match or offers creation with the plate prefilled. Mechanics receive a not-found message without write actions. Vehicle forms optionally search customers in pages of 20; customer pages list their vehicles in pages of 10 and offer preselected creation. A stale workshop form is rejected; tenant IDs in input never authorize access. There are no Work Orders or service-history screens yet.

**ID:** Pencarian pelat dari ringkasan/Kendaraan membuka kecocokan normalisasi penuh atau menawarkan tambah dengan pelat terisi. Mekanik mendapat pesan belum ditemukan tanpa aksi tulis. Form kendaraan menyediakan pencarian pelanggan opsional, 20 hasil per halaman; detail pelanggan memuat kendaraannya (10 per halaman) serta tautan tambah dengan pelanggan terpilih. Form dengan konteks bengkel lama ditolak; ID tenant dari input bukan bukti akses. Belum ada Work Order atau layar riwayat servis.

**EN — Local validation:** PHP 8.4.26 / PostgreSQL 18.6: **296 tests / 1,434 assertions**, 3 JavaScript customer-picker tests, Pint, Composer validation, Blade compilation, and frontend build pass. Automated Edge at 1280×900 and 390×844 passes lookup/create/edit, optional customer search/detach, duplicates, pagination, role restrictions, tenant isolation, and stale forms without JavaScript errors or overflow. The application has 10 applied migrations; existing account/workshop/customer/trial counts were preserved. See [Sprint 1 evidence](docs/SPRINT_1_CUSTOMER_VEHICLE.md) and the [C02/C03 checklist](docs/MANUAL_TESTING.md#c02c03-laptop-follow-up--pemeriksaan-laptop-c02c03). SMTP delivery was not retested.

**ID — Validasi lokal:** PHP 8.4.26 / PostgreSQL 18.6: **296 tes / 1.434 assertion**, 3 tes JavaScript pemilih pelanggan, Pint, validasi Composer, kompilasi Blade, serta build frontend lolos. Edge otomatis 1280×900 dan 390×844 lolos untuk pencarian/tambah/edit, cari/lepas pelanggan, duplikat, pagination, pembatasan role, isolasi tenant, serta form lama tanpa error JavaScript atau overflow. Aplikasi memiliki 10 migration Ran; jumlah akun/bengkel/pelanggan/trial lama terjaga. Lihat [bukti Sprint 1](docs/SPRINT_1_CUSTOMER_VEHICLE.md) dan [checklist C02/C03](docs/MANUAL_TESTING.md#c02c03-laptop-follow-up--pemeriksaan-laptop-c02c03). Pengiriman SMTP tidak diuji ulang.

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

**EN:** Tests use PHPUnit with array-backed sessions/cache and synchronous queues. The required test database is `bengkel_saas_testing` on PostgreSQL. Create this dedicated disposable database and configure `.env.testing` (including `APP_KEY`, `DB_*`, and empty `DB_URL`) before running feature tests. F08 refuses unsafe resolved configuration before `RefreshDatabase`, which may rebuild the testing schema. The database name alone cannot prove a host is safe: verify `DB_HOST` and credentials too. Never target production or persistent application data. Feature tests disable Vite lookup; validate assets separately with `npm run build`.

**ID:** Test menggunakan PHPUnit, session/cache array, dan queue sinkron. Database tes yang diwajibkan adalah `bengkel_saas_testing` di PostgreSQL. Buat database sementara khusus ini dan konfigurasi `.env.testing` (termasuk `APP_KEY`, `DB_*`, dan `DB_URL` kosong) sebelum menjalankan feature test. F08 menolak konfigurasi hasil resolusi yang tidak aman sebelum `RefreshDatabase`, yang dapat membangun ulang schema tes. Nama database saja tidak membuktikan host aman: verifikasi juga `DB_HOST` dan kredensial. Jangan arahkan tes ke production atau data aplikasi persisten. Feature test menonaktifkan pencarian Vite; validasi aset terpisah melalui `npm run build`.

## Project documentation / Dokumentasi project

- [Agent instructions / Instruksi agent](AGENTS.md)
- [Product / Produk](docs/PRODUCT.md)
- [Architecture / Arsitektur](docs/ARCHITECTURE.md)
- [Database](docs/DATABASE.md)
- [Roadmap](docs/ROADMAP.md)
- [Manual testing / Pengujian mandiri](docs/MANUAL_TESTING.md)
- [F08 Foundation review / Laporan Foundation F08](docs/FOUNDATION_REVIEW.md)
