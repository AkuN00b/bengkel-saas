# BengkelOS — Manual Testing / Pengujian Mandiri

> Reviewed / Ditinjau: 2026-11-06
>
> Baseline F02: `main` after / setelah PR #1, tag `f02-authentication-v1`; F03 tag: `f03-workshop-tenant-v1`; F04 tag: `f04-membership-roles-v1`; F05 tag: `f05-trial-provisioning-v1`; F06 target tag: `f06-active-workshop-v1`. / Tag target F06: `f06-active-workshop-v1`.
>
> Laptop status / Status laptop: preparation and HTTP checks verified by Codex on 2026-11-06 at commit `5faf95f`; current browser flows and real SMTP delivery remain unverified. / Persiapan dan pemeriksaan HTTP diverifikasi Codex pada 2026-11-06 di commit `5faf95f`; alur browser versi saat ini dan pengiriman SMTP nyata belum diverifikasi.

**ID:** `[x]` berarti seluruh kondisi pada item itu sudah diverifikasi; `[ ]` tetap kosong jika belum diuji, baru sebagian, atau perlu Anda coba manual. Centang persiapan berasal dari terminal/HTTP Codex, bukan pengujian browser oleh user. Hasil versi F02 lama tidak otomatis berlaku untuk F06. Perubahan email/countdown lokal lama masih tersimpan di stash dan bukan bagian versi `5faf95f` yang diuji di sini.

**EN:** `[x]` means every condition in that item was verified; `[ ]` stays empty for untested, partial, or pending manual checks. Preparation checks come from Codex terminal/HTTP verification, not user browser testing. Earlier F02 results do not automatically verify F06. Older local email/countdown changes remain stashed and are not part of the `5faf95f` version checked here.

**ID:** Persiapan teknis yang dicentang sudah selesai untuk versi saat ini; Anda tidak perlu mengulang blok perintah hanya untuk mulai tes. Langsung mulai dari item browser pertama pada [pemeriksaan F06](#f06-laptop-follow-up--pemeriksaan-laptop-f06); pemeriksaan autentikasi/SMTP yang belum dicentang tetap perlu dilakukan.

**EN:** Checked preparation is complete for the current version; rerunning command blocks is unnecessary just to start testing. Begin with the first browser item in the [F06 checklist](#f06-laptop-follow-up--pemeriksaan-laptop-f06); unchecked authentication/SMTP checks remain outstanding.

**ID — F07:** Catatan dan centang hasil F06 di dokumen ini dipertahankan. Untuk UI baru setelah pull F07, mulai dari [checklist F07](#f07-laptop-follow-up--pemeriksaan-laptop-f07). Semua item F07 tetap kosong sampai dicoba di laptop. Tag target kode: `f07-application-shell-v1`.

**EN — F07:** Existing F06 notes and checkmarks are preserved. Start the new UI checks in the [F07 checklist](#f07-laptop-follow-up--pemeriksaan-laptop-f07) after pulling F07. Its boxes remain unchecked until verified on the laptop. Target code tag: `f07-application-shell-v1`.

## 0. Start here after F05 merge / Mulai dari sini setelah merge F05

**ID:** Ini urutan praktis persiapan dan tes manual. Persiapan yang sudah diverifikasi Codex dicentang; tes manual tetap kosong sampai dicoba pada versi saat ini. Detail dan penanganan kondisi khusus ada pada bagian berikutnya. Tag milestone F05: `f05-trial-provisioning-v1`; tag hanya menyimpan versi kode, bukan database atau `.env`.

**EN:** This is the practical preparation and manual-test sequence. Codex-verified preparation is checked; manual tests stay unchecked until performed on the current version. Later sections provide details. F05 milestone tag: `f05-trial-provisioning-v1`. Tags preserve code, not the database or `.env`.

### A. Update and start / Perbarui dan jalankan

**ID:** Dari root repo, periksa perubahan lokal dahulu; simpan pekerjaan lokal sebelum switch/pull. Jangan menimpa `.env` atau membuat ulang `APP_KEY` yang sudah digunakan. Setelah merge dan tag tersedia, jalankan:

**EN:** From the repo root, inspect and preserve local changes before switching/pulling. Preserve the existing `.env` and application key. After the merge/tag are available, run:

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
git show --no-patch --oneline f05-trial-provisioning-v1
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

- [x] Codex, 2026-11-06: branch `main` pada `5faf95f` sudah sinkron dengan GitHub; tag F01–F06 tersedia lokal. / `main` at `5faf95f` is synchronized with GitHub; F01–F06 tags are available locally.
- [x] Codex, 2026-11-06: Herd dan PostgreSQL aktif; IIS dihentikan sementara agar tidak bentrok. / Herd and PostgreSQL are running; IIS is temporarily stopped to avoid a conflict.
- [x] Codex, 2026-11-06: `php artisan config:clear` berhasil; `APP_URL` cocok dengan `http://bengkel-saas.test`. / Configuration was cleared and the configured URL matches Herd.
- [x] Codex, 2026-11-06: `php artisan migrate` berhasil; `migrate:status` memastikan seluruh 8 migration Ran, termasuk kedua migration F05. / Migration execution succeeded; all 8 migrations, including both F05 migrations, show Ran.
- [x] Codex, 2026-11-06: `npm.cmd run build` berhasil; `/`, `/login`, `/register`, dan aset CSS login merespons HTTP 200 melalui Herd. / The frontend build passed; the home/login/register pages and login CSS return HTTP 200 through Herd.
- [x] `composer install` dan `npm.cmd ci` dijalankan ulang untuk checkout F06 ini. Belum dijalankan ulang; dependency lokal yang ada berhasil dipakai untuk migration/build. / Dependency installation has not been rerun for this F06 checkout; existing local dependencies worked for migration/build.
- [x] User: buka aplikasi di browser dan pastikan CSS benar secara visual, tanpa halaman error. / Open the application and verify visual styling and absence of an error page.

**ID:** Jangan seed ulang atau menjalankan `migrate:fresh` untuk tes manual ini. Item instalasi dependency yang kosong bukan berarti aplikasi belum bisa dites; migration, build, dan HTTP sudah diperiksa terpisah di atas.

**EN:** Do not reseed or run `migrate:fresh` for these manual checks. The unchecked dependency-installation item does not block browser testing; migration, build, and HTTP were verified separately above.

### B. New owner trial / Trial owner baru

- [x] Logout lalu buka `/register`: field **Nama bengkel** tersedia. Nama bengkel kosong, password pendek, dan konfirmasi tidak cocok harus ditolak. / Verify the workshop-name field and validation while logged out.
- [x] Daftarkan **akun uji baru**, gunakan email yang Anda kuasai dan nama bengkel yang mudah dikenali. Harus masuk ke `/dashboard` dan nama pengguna tampil. / Register a new test account; the account page opens and displays the user name.
- [x] Bila memeriksa backend melalui pgAdmin atau pembaca database lokal, cocokkan data di tabel berikut tanpa mengubahnya. Ini belum terlihat di dashboard F05. / Optionally inspect these local tables read-only; F05 does not yet display this information on the dashboard.

| Tabel / Table | Filter dan hasil yang diharapkan / Filter and expected result |
| --- | --- |
| `users` | Cari email akun uji baru; satu user, `is_super_admin=false`. / Find the new test email; one non-admin user. |
| `workshop_user` | Filter `user_id` dari user tadi; satu membership `role=owner`, catat `workshop_id`. / One owner membership for that user. |
| `workshops` | Cari ID dari membership; nama sesuai form. / The matching workshop name equals the submitted name. |
| `subscriptions` + `plans` | Filter `workshop_id` tadi; satu subscription `trialing`, plan berkode `trial` dengan harga 0; `trial_ends_at` dan `ends_at` sama, tepat 14 hari setelah `starts_at` (UTC). / One free trial, with matching deadlines 14 days after its UTC start. |

- [x] Logout, coba login salah lalu benar; logout lagi dan akses langsung `/dashboard` harus meminta login. / Check incorrect/correct login and protected access after logout.
- [x] Akun lama tetap bisa login. Akun lama tidak otomatis memperoleh bengkel/trial; jangan menganggap itu kegagalan upgrade. / Existing accounts still log in; no retroactive workshop/trial is expected.
- [x] Periksa form pada ukuran HP; tidak melebar horizontal dan tombol nyaman ditekan. / Check mobile form layout and usable buttons.

**ID:** Jika belum nyaman membaca database, jangan menebak hasil backend sebagai lolos. Tandai belum diperiksa; tes otomatis bagian 6 membuktikan provisioning/rollback secara terpisah. Jangan mengubah waktu server, status subscription, role, atau data aplikasi untuk mensimulasikan kegagalan. Tidak perlu menunggu 14 hari; tes batas waktu sudah otomatis. Menu billing, pembayaran, dan enforcement trial belum termasuk F05. Pemilihan workshop ditambahkan di F06 di bawah.

**EN:** If database inspection is unfamiliar, leave backend checks unverified; section 6 covers automated provisioning/rollback tests. Do not alter server time, subscription status, roles, or application data to simulate failures. Boundary tests avoid waiting 14 days. Billing, payments, and trial access enforcement are not part of F05. F06 adds workshop selection below.

### C. Real reset email — still pending / Email reset nyata — masih tertunda

- [x] Ikuti **bagian 5**: konfigurasi SMTP, email benar-benar sampai, tautan memakai `APP_URL` laptop, reset berhasil, password lama gagal dan baru berhasil. / Follow section 5 to verify real delivery and the complete password-reset flow.
- [x] Tautan yang sudah dipakai harus ditolak saat disubmit ulang; periksa ulang session browser kedua setelah reset. / Verify token reuse fails and an older browser session requires login again.
- [ ] Isi hasil di **bagian 7**, termasuk langkah yang gagal dan error yang sudah disensor. Suite tes lokal **bagian 6** opsional, hanya dengan database tes terpisah yang sudah diverifikasi. / Record results in section 7; the optional local suite requires the verified dedicated testing database.

**ID:** Jangan kirim isi `.env`, password, atau tautan/token reset saat melaporkan kendala. Berhasil pull atau lolos tes cloud belum berarti pengujian laptop/SMTP di atas sudah lolos.

**EN:** Never include `.env` contents, passwords, or reset links/tokens in reports. Pulling successfully or passing cloud tests does not mark these laptop/SMTP checks as complete.

## 1. Current stage / Tahap sekarang

| Tahap / Stage | Status |
| --- | --- |
| F01 — Laravel bootstrap | Selesai / Complete; tag `f01-bootstrap-v1` |
| F02 — Authentication | Sudah di-merge ke `main` / Merged into `main`; tag `f02-authentication-v1` |
| Laptop preparation / Persiapan laptop | Diverifikasi Codex 2026-11-06: service, migration, build, HTTP / Services, migration, build, HTTP verified |
| F02–F06 — Current browser flows + real email / Alur browser versi saat ini + email nyata | Belum dikonfirmasi / Not confirmed |
| F03 — Workshop Tenant | Sudah di-merge; tag `f03-workshop-tenant-v1` / Merged; tagged |
| F04 — Membership + Roles | Sudah di-merge; tag `f04-membership-roles-v1` / Merged; tagged |
| F05 — Plans + Subscription + Trial | Sudah di-merge; tag `f05-trial-provisioning-v1` / Merged; tagged |
| F06 — Active Workshop + Tenant Isolation | Sudah di-merge; tag `f06-active-workshop-v1` / Merged; tagged |
| F07 — Application UI Shell | Tersedia dalam perubahan F07 / Implemented in the F07 change |
| F08 — Foundation Test & Hardening | Belum diimplementasikan / Not implemented |

**ID:** F02 menyediakan registrasi akun, login/ingat saya, logout, lupa/reset kata sandi, dan halaman akun `/dashboard`. Pada F05 registrasi membuat workshop baru, membership owner, dan trial gratis 14 hari; akun lama tidak otomatis diubah. Ini masih Phase 1 — Foundation, belum seluruh foundation selesai. Checklist ini bukan izin otomatis melanjutkan roadmap.

**EN:** F02 provides account registration, login/remember-me, logout, forgot/reset password, and a protected `/dashboard` account page. F05 registration creates a new workshop, owner membership, and free 14-day trial; existing accounts are not automatically changed. Phase 1 — Foundation is still in progress. This checklist does not authorize automatic roadmap implementation.

**ID — Pembaruan 31 Oktober:** User mengizinkan F03 dilanjutkan meskipun pengujian mandiri F02 belum sempat dilakukan. Status laptop dan SMTP tetap belum dikonfirmasi; kelanjutan implementasi bukan bukti bahwa pemeriksaan tersebut lolos.

**EN — October 31 update:** The user authorized F03 despite deferring F02 manual testing. Laptop/SMTP checks remain unconfirmed; continuing development does not establish that they pass.

## 2. Evidence already available / Yang sudah diverifikasi

**ID:** Saat F02 dikerjakan pada 27 Oktober, pemeriksaan berikut lolos di lingkungan cloud terpisah. Ini bukan bukti bahwa konfigurasi laptop atau SMTP pribadi sudah benar.

**EN:** These checks passed in an isolated cloud environment on October 27. They do not verify the laptop configuration or personal SMTP service.

- PHPUnit: **41 tests / 241 assertions**, PHP 8.4 + PostgreSQL 17.
- `vendor/bin/pint --test`, `composer validate --strict`, `php artisan view:cache`, `npm run build`.
- Browser desktop 1280×900 dan HP / mobile 390×844: register, logout, login, salah password / invalid password, submit lupa password / forgot-password submission; tanpa error JavaScript atau halaman melebar / no JavaScript errors or horizontal overflow.
- Tes otomatis / automated tests: validasi, CSRF, session, throttling, pembatasan tamu / guest restrictions, XSS escaping, URL reset, token salah/kedaluwarsa/lintas akun/dipakai ulang / invalid, expired, cross-account, and reused tokens.

**ID:** Pada 6 November, Codex memverifikasi persiapan lokal, migration, build, respons HTTP halaman publik dan CSS (lihat bagian 0A). Yang belum diperiksa pada F06: alur browser terautentikasi, email reset sampai ke kotak masuk nyata, tautan tersebut membuka aplikasi lokal yang benar, dan keseluruhan reset melalui email nyata. Form lupa password yang menampilkan pesan sukses belum membuktikan email diterima; tes cloud memakai notifikasi simulasi.

**EN:** On November 6, Codex verified local preparation, migration, build, and public-page/CSS HTTP responses (section 0A). Outstanding F06 checks: authenticated browser flows, delivery to a real inbox, the received link opening the correct local application, and the complete real-email reset flow. A successful forgot-password form response does not prove delivery; cloud tests used fake notifications.

## 3. Prepare the laptop / Persiapan laptop

**ID:** Jalankan dari root repository melalui terminal Herd/PowerShell. Jika ada perubahan lokal, simpan dan tinjau dulu; jangan gunakan reset paksa. Gunakan akun/data uji.

**EN:** Run from the repository root in a Herd/PowerShell terminal. Preserve and review local changes before updating; do not force-reset them. Use test accounts/data.

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
git log -1 --oneline
git tag --list
php --version
composer install
npm.cmd ci
```

- [x] Codex, 2026-11-06: `main` sudah diperbarui dan tag F01–F06 tersedia. / `main` is updated and F01–F06 tags are available.
- [x] Codex, 2026-11-06: PHP Herd 8.4.26 tersedia melalui PATH sesi terminal; tidak mengubah PATH sistem. Lihat [README](../README.md#local-setup--persiapan-lokal). / Herd PHP 8.4.26 is available with a session-local PATH; the system PATH was not changed.
- [x] Codex, 2026-11-06: `.env` lokal tersedia dan `APP_KEY` terisi; keduanya tidak ditimpa atau diregenerasi. Nilai rahasia tidak ditampilkan. / Existing environment and application key are present and preserved; secret values were not displayed.
- [x] Codex, 2026-11-06: PostgreSQL lokal aktif dan koneksi aktual menunjuk database aplikasi lokal `bengkel_saas`. / PostgreSQL is running and the actual connection targets the local application database.
- [x] Codex, 2026-11-06: `APP_URL` tepat `http://bengkel-saas.test`, tanpa `/login`. / The configured application origin matches Herd.

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate
npm.cmd run build
```

**ID:** F02 tidak menambah migration. Jika versi yang ditarik sudah menyertakan F03, ada satu migration baru: `2026_10_31_000000_create_workshops_table`. Jalankan `php artisan migrate` biasa, lalu `php artisan migrate:status` dan pastikan migration itu berstatus Ran. Belum ada menu workshop; ini memang baru fondasi database. Jangan jalankan `migrate:fresh` atau `migrate:refresh` pada database aplikasi yang berisi data. Gunakan Herd lalu buka `http://bengkel-saas.test`; jika memakai `php artisan serve`, sesuaikan `APP_URL` dengan alamat server tersebut.

**EN:** F02 adds no migrations. A version containing F03 adds `2026_10_31_000000_create_workshops_table`; apply it with ordinary `php artisan migrate`, then check for Ran in `migrate:status`. There is no workshop menu yet: this is the database foundation. Never run `migrate:fresh` or `migrate:refresh` against application data. Open the Herd URL, or match `APP_URL` to the server address when using `php artisan serve`.

- [x] Codex, 2026-11-06: konfigurasi dibersihkan, seluruh migration Ran, build berhasil, dan halaman publik merespons HTTP 200. / Configuration clear, migrations, build, and public-page HTTP checks passed.
- [ ] User: periksa CSS dan rendering halaman `/` di browser. / Verify home-page styling and rendering visually in the browser.

## 4. Browser checklist / Checklist browser

**ID:** Centang hanya setelah dicoba di laptop. Gunakan jendela biasa dan incognito sebagai dua session terpisah. Tunggu 60 detik bila terkena throttling sebelum melanjutkan pengujian berikutnya.

**EN:** Check an item only after verifying it on the laptop. Use normal and private windows as separate sessions. Allow 60 seconds after throttling before continuing other checks.

| Selesai / Done | Langkah / Action | Hasil yang diharapkan / Expected result |
| --- | --- | --- |
| [ ] | Sebagai tamu, buka `/dashboard`. / Open `/dashboard` as a guest. | Dialihkan ke `/login`. / Redirects to login. |
| [ ] | Daftar di `/register` dengan nama bengkel, email uji, dan password minimal 12 karakter; konfirmasi sama. / Register with a workshop name and matching password confirmation. | Masuk ke `/dashboard`, nama tampil. / Signed in; account name displayed. |
| [ ] | Keluar, lalu coba daftar dengan email yang sama; coba juga password pendek atau konfirmasi berbeda. / Log out; try duplicate registration, a short password, and mismatched confirmation. | Pesan validasi tampil; akun duplikat tidak dibuat. / Validation messages; no duplicate account. |
| [ ] | Login dengan email terdaftar dan password salah, lalu password benar. / Try an incorrect password, then the correct one. | Yang salah ditolak; yang benar masuk ke akun. / Incorrect credentials rejected; correct ones accepted. |
| [ ] | Klik **Keluar**, lalu ketik `/dashboard` dan refresh. / Log out, navigate to `/dashboard`, and refresh. | Harus login lagi; jangan menilai dari halaman lama di browser cache. / Login required; do not judge by a cached page. |
| [ ] | Login dengan **Ingat saya**, tutup seluruh browser tanpa logout, buka kembali. / Remember-me login, close the browser without logging out, and reopen. | Masih dikenali jika cookie disimpan; logout tetap mengakhiri akses. Mode incognito/pembersihan cookie tidak cocok untuk tes ini. / Remembered when cookies persist; logout ends access. Avoid private mode or cookie-clearing settings. |
| [ ] | Sebagai tamu, kirim 5 login gagal dengan email yang sama, lalu coba password benar sebelum 60 detik. / Make 5 failed logins for one email; try the correct password within 60 seconds. | Diblokir sementara; setelah masa tunggu bisa login. / Temporarily blocked; login works after the wait. |
| [ ] | Periksa form pada lebar sekitar 390 px lewat DevTools atau HP. / Check forms at approximately 390 px using DevTools or a phone. | Field/tombol terbaca, tidak perlu scroll horizontal. / Readable fields/buttons; no horizontal scrolling. |

## 5. Real reset email — priority / Email reset nyata — prioritas

**ID:** Ini bagian utama yang belum diverifikasi kemarin. SMTP sandbox hanya membuktikan email diterima sandbox. Untuk bukti sampai kotak masuk nyata, gunakan penyedia SMTP dan email yang Anda kuasai. Jangan menyalin password SMTP atau tautan/token reset ke GitHub, screenshot publik, atau checklist ini.

**EN:** This is the main unverified item. An SMTP sandbox proves sandbox capture only. Verify real inbox delivery using an SMTP provider and an address you control. Do not publish SMTP credentials or reset links/tokens in GitHub, screenshots, or this checklist.

- [x] Di `.env`, isi `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, dan `MAIL_SCHEME` sesuai petunjuk penyedia bila diperlukan. Jangan gunakan `MAIL_MAILER=log`. / Configure SMTP and its required scheme using provider instructions; do not log reset emails.
- [x] Jalankan `php artisan config:clear` setelah perubahan konfigurasi; mulai ulang server dev jika dipakai. `.env` lama tidak otomatis diperbarui saat pull. / Clear configuration and restart a dev server if applicable; pulling does not update an existing `.env`.
- [x] Logout, buka `/forgot-password`, kirim email akun uji yang benar-benar bisa diperiksa. / Log out and request a reset for the test account's accessible email.
- [x] Email benar-benar diterima di inbox/spam, bukan hanya pesan sukses di website. Catat waktu penerimaan, tanpa token. / Confirm inbox/spam delivery, not just a website success message; record delivery time without the token.
- [x] Tautan email memakai `APP_URL` yang benar dan membuka form reset di laptop. Domain `.test` lokal tidak otomatis tersedia di HP/perangkat lain. / The emailed link uses the correct `APP_URL` and opens the laptop's reset form; local `.test` domains are not automatically accessible on other devices.
- [x] Isi password baru dan konfirmasinya; setelah berhasil diarahkan ke login. Password lama gagal, password baru berhasil. / Reset with a matching new password; return to login; old password fails and the new one works.
- [x] Logout lalu buka tautan yang sama dan coba submit lagi: harus ditolak. Form yang masih bisa dibuka bukan berarti token masih dapat dipakai. / Log out and resubmit the same link: it must fail; rendering a form does not prove token validity.
- [x] Jika ada session akun yang sama di browser kedua sebelum reset, refresh `/dashboard` sesudah reset: harus diminta login lagi. / Refresh an existing session in a second browser after reset; it must require login again.
- [x] Minta reset memakai email tidak terdaftar: pesan publik sama, tidak mengungkap apakah akun ada. / Unknown emails receive the same public response, without account disclosure.

**ID:** Token kedaluwarsa setelah 60 menit. Tidak perlu menunggu satu jam hanya untuk mengulang tes cloud; kasus token salah, kedaluwarsa, lintas akun, dan sekali pakai sudah ada dalam tes otomatis.

**EN:** Tokens expire after 60 minutes. Waiting an hour solely to repeat the cloud checks is unnecessary; invalid, expired, cross-account, and single-use cases already have automated coverage.

## 6. Optional local test suite / Suite tes lokal opsional

**ID:** Ini untuk memastikan suite juga berjalan di laptop. Lakukan hanya setelah database khusus tes tersedia; tes memakai `RefreshDatabase` dan dapat membangun ulang schema target. Jangan arahkan ke database aplikasi atau production.

**EN:** Use this to verify the suite on the laptop. Set up a dedicated testing database first: `RefreshDatabase` can rebuild the target schema. Never target application or production data.

1. Pastikan database PostgreSQL `bengkel_saas_testing` sudah ada. Script `database/setup-local.sql` F01 membuat database ini, tetapi jangan jalankan ulang script bila role/database sudah ada. / Confirm the dedicated database exists; do not rerun the F01 setup script against existing names.
2. Jika `.env.testing` belum ada, salin `.env.example` ke `.env.testing` saja. / Copy `.env.example` to `.env.testing` only if it is absent.
3. Di `.env.testing`, isi `APP_ENV=testing`, `DB_CONNECTION=pgsql`, `DB_DATABASE=bengkel_saas_testing`, `DB_URL=` (kosong), serta `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` lokal yang sesuai. Isi `MAIL_MAILER=array`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`. / Configure the dedicated connection and testing drivers in `.env.testing`.
4. Hanya jika `APP_KEY` di `.env.testing` kosong, jalankan `php artisan key:generate --env=testing`. Jangan regenerasi key aplikasi di `.env`. / Generate a testing key only if missing; preserve the application key.
5. Bersihkan cache dan periksa database aktual sebelum menjalankan suite. Konfigurasi environment terminal bisa mengalahkan file konfigurasi. / Clear cached configuration and inspect the actual connection; terminal environment overrides can affect it.

```powershell
php artisan config:clear
php artisan tinker --env=testing --execute="dump(config('database.default'), DB::selectOne('select current_database() as name')->name);"
```

**ID:** Output harus `pgsql` dan `bengkel_saas_testing`. Jika berbeda atau error koneksi, berhenti dan benahi konfigurasi sebelum menjalankan tes.

**EN:** Expect `pgsql` and `bengkel_saas_testing`. Stop and fix the configuration if either differs or the connection fails.

```powershell
php artisan test
composer validate --strict
php vendor/bin/pint --test
npm.cmd run build
```

- [ ] Suite dan pemeriksaan di atas lolos. Baseline F02: 41 tes / 241 assertion; F03: 51 tes / 275 assertion; F04: 72 tes / 354 assertion; F05: 100 tes / 468 assertion. / Checks pass; F02: 41 tests / 241 assertions; F03: 51 tests / 275 assertions; F04: 72 tests / 354 assertions; F05: 100 tests / 468 assertions.

**ID:** Untuk memeriksa F03 saja pada database tes yang sudah diverifikasi di atas, jalankan `php artisan test --filter=WorkshopTest` (10 tes). Ini memeriksa penyimpanan workshop, constraint, default, definisi relasi, dan rollback schema. F04 menambahkan query/attach relasi nyata beserta tes membership; pengujian isolasi tenant melalui HTTP menyusul F06. Jangan menyimpulkan isolasi tenant sudah selesai dari tes schema ini.

**EN:** On the verified testing database, run `php artisan test --filter=WorkshopTest` for the 10 F03 checks. They cover persistence, constraints, defaults, relationship definitions, and schema rollback. F04 adds actual relationship queries/attach and membership tests; HTTP tenant-isolation tests belong to F06. Schema tests do not prove complete tenant isolation.

### F04 laptop follow-up / Pemeriksaan laptop F04

**ID:** Bila versi yang ditarik sudah memuat F04, jalankan `php artisan migrate` lalu `php artisan migrate:status`. Pastikan `2026_10_31_000001_create_workshop_user_table` dan `2026_10_31_000002_add_is_super_admin_to_users_table` berstatus Ran. User/workshop lama harus tetap tersedia; pada F04 registrasi tidak memberi membership; pada F05 hanya memberi owner untuk workshop baru, tidak memberi admin platform. Belum ada menu anggota/role, jadi tidak ada alur UI baru yang perlu dicari. Jangan mengubah flag admin atau memberikan role lewat input publik untuk mencoba fitur ini.

**EN:** After updating to a version containing F04, run `php artisan migrate` and `php artisan migrate:status`; both new F04 migrations should show Ran. Existing users/workshops remain available; F04 registration did not assign membership; F05 grants only owner membership for its new workshop, never platform admin. There is no member/role menu yet. Do not use public input to attempt admin or role assignment.

- [x] Codex, 2026-11-06: kedua migration F04 berstatus Ran. / Both F04 migrations show Ran.
- [ ] User: akun lama tetap bisa login setelah migration F04–F05. / Existing accounts still log in after the F04–F05 migrations.
- [ ] Pada database tes terpisah yang diverifikasi di bagian 6, `php artisan test --filter=WorkshopMembershipTest` lolos (21 tes); suite penuh F04: 72 tes / 354 assertion. / On the verified dedicated test database, membership tests pass (21 tests); full F04 suite: 72 tests / 354 assertions.
- [ ] Hasil laptop dicatat, bukan diasumsikan lolos karena tes cloud sudah berhasil. / Laptop results are recorded rather than inferred from cloud tests.

### F05 laptop follow-up / Pemeriksaan laptop F05

- [x] Codex, 2026-11-06: `php artisan migrate`, `php artisan migrate:status`, dan `npm.cmd run build` berhasil. Kedua migration F05 `2026_10_31_000003_create_plans_table` dan `2026_10_31_000004_create_subscriptions_table` berstatus Ran. Tidak menjalankan seed ulang. / Both F05 migrations show Ran and the frontend build passed, without reseeding.
- [ ] Daftar akun uji baru dengan nama bengkel. Harus masuk ke halaman akun; backend membuat workshop baru, owner membership, dan trial gratis 14 hari. / Register a new test account with a workshop name; the account page opens and provisioning completes.
- [ ] Nama bengkel kosong ditolak dengan pesan validasi. Paket/role tidak dapat dipilih lewat form registrasi. / Missing workshop names are rejected; registration offers no plan/role selection.
- [ ] Akun lama masih bisa login. Tidak ada trial/workshop retroaktif untuk akun lama, menu billing atau tombol pembayaran pada F05. F06 menambah pemilihan workshop dan mengarahkan akun tanpa membership ke halaman informasi. / Existing login still works; no retroactive provisioning, billing menu or payment button is expected. F06 adds workshop selection and a safe landing page for accounts without membership.
- [ ] Pada database tes terpisah sesuai bagian 6, jalankan `php artisan test --filter=OwnerProvisioningTest` dan `php artisan test --filter=SubscriptionTest`, atau seluruh suite (100 tes / 468 assertion). Tes membuktikan semua tahap rollback saat gagal; jangan sengaja merusak database aplikasi untuk mengulangnya. / On the verified dedicated test database, run the provisioning/subscription tests or full suite; do not corrupt application data to simulate failures.

**ID:** Trial dinilai tidak berlaku tepat setelah 14 hari oleh `isTrialing()`; belum ada job pengubah status atau pembatas akses subscription. Tidak perlu menunggu 14 hari untuk tes manual karena batas waktu sudah diuji otomatis. Harga Starter/Pro belum ditentukan dan paketnya belum aktif. Pemeriksaan email reset nyata di atas tetap tertunda.

**EN:** `isTrialing()` returns false at the 14-day deadline; automatic status transitions and subscription access enforcement are not added. Automated boundary tests avoid waiting 14 days manually. Starter/Pro remain inactive and unpriced. Real reset-email verification above is still pending.

### F06 laptop follow-up / Pemeriksaan laptop F06

**ID — Mulai tes browser di sini:** Persiapan service, konfigurasi, migration, build, dan HTTP sudah dicentang pada bagian 0A/3. Blok di bawah adalah panduan persiapan ulang saat dibutuhkan; tidak perlu dijalankan lagi sekarang. Seluruh item F06 di bawah masih kosong karena alur tersebut belum dites pada browser laptop versi saat ini. Mulai dengan registrasi akun uji baru atau login akun yang punya satu bengkel.

**EN — Start browser testing here:** Services, configuration, migration, build, and HTTP preparation are checked in sections 0A/3. The block below is a preparation reference, not something to repeat now. All F06 items below remain unchecked because the current laptop browser flows have not been verified. Start with a new test registration or an account with one workshop.

**ID:** Setelah F06 di-merge dan tag tersedia, ikuti urutan berikut. Tidak ada migration baru F06; migration lama tetap harus sudah berjalan. Gunakan database lokal/data uji dan jangan menandai hasil sebagai lolos sebelum dicoba. Tag versi kode: `f06-active-workshop-v1`; tag tidak menyimpan database/`.env`.

**EN:** After the F06 merge/tag, follow this order on local test data. F06 adds no migration, but prior migrations must be applied. Do not mark unperformed checks as passed. The code tag is `f06-active-workshop-v1`, excluding database/`.env`.

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
npm.cmd run build
```

- [ ] Login dengan akun yang memiliki **satu bengkel**, atau daftar akun uji baru. Dashboard otomatis menampilkan nama bengkel miliknya. / A single-membership account automatically sees its own workshop on the dashboard.
- [ ] Buka **Pilih bengkel lain**. Daftar hanya memuat bengkel yang boleh diakses user tersebut. Tidak ada form penambahan membership baru di F06. / The switcher lists only that user's memberships; membership creation UI is not provided.
- [ ] Jika sudah memiliki akun uji dengan **lebih dari satu membership sah**, logout/login kembali: diarahkan memilih bengkel sebelum dashboard. Pilih bengkel pertama, lalu ganti ke kedua; nama aktif berubah dan tetap benar setelah refresh. / A multi-membership account must select first; switching changes the displayed workshop and persists on refresh.
- [ ] Jika belum punya fixture multi-membership, tandai kasus tersebut **belum diperiksa** atau gunakan suite tes terpisah di bawah. Jangan memberi role/admin ke akun asli atau mengedit database production untuk menyiapkannya. / Leave missing-fixture cases unverified or use the dedicated test suite; never change real/production privileges to prepare fixtures.
- [ ] Login di browser/incognito terpisah sebagai akun B yang hanya memiliki bengkel B. A hanya melihat bengkel A; B hanya bengkel B. Parameter query `workshop_id` bukan cara mengganti bengkel dan tidak boleh menampilkan nama tenant lain. / Separate A/B sessions show only their own workshops; query tenant IDs cannot switch context.
- [ ] Pada data uji saja, jika memeriksa DevTools, ganti hidden `workshop_id` form pemilihan ke ID bengkel akun lain lalu submit: harus 404, tanpa data bengkel lain dan tanpa mengganti pilihan valid sebelumnya. Kasus ini juga sudah diuji otomatis. Jangan kirim cookie/token CSRF dalam laporan. / A forged selection must return 404 without exposing tenant data or changing a valid selection; do not include cookies/CSRF tokens in reports.
- [ ] Jika tersedia fixture yang membership-nya **dicabut oleh pengelola melalui proses tepercaya**, refresh dashboard: akses tenant lama harus dihentikan dan diminta memilih ulang. Jangan merusak data aplikasi untuk membuat skenario ini; tes otomatis mencakup pencabutan dan penghapusan bengkel. / Revoked membership must lose dashboard access on refresh; use dedicated tests if a safe fixture is unavailable.
- [ ] Login akun lama yang **belum punya membership**: muncul pesan belum tergabung dan tombol keluar, tanpa loop redirect atau data bengkel lain. Akun itu tetap bisa login; akses tenantnya yang belum tersedia. / A zero-membership account gets an informational page and logout, without redirect loops or tenant data.
- [ ] Logout dari tiap akun, lalu akses `/dashboard` atau `/workshops/select`: harus login lagi. Cek tampilan pemilihan pada ukuran HP tanpa scroll horizontal. / Logout blocks dashboard/selector access; verify mobile layout.
- [ ] Pada database tes terpisah yang diverifikasi di bagian 6: `php artisan test --filter=ActiveWorkshopTest`, lalu suite penuh. Baseline F06: **130 tes / 593 assertion**; jangan jalankan `RefreshDatabase` pada data aplikasi. / Use the verified dedicated database; F06 baseline is 130 tests / 593 assertions.
- [ ] Pemeriksaan SMTP/email reset pada bagian 5 tetap dilakukan saat ada waktu; hasilnya masih belum dikonfirmasi. / Real SMTP verification remains pending.

**ID:** F06 membuktikan konteks bengkel dan dashboard saat ini, bukan seluruh modul operasional yang belum dibuat. Membership yang valid belum otomatis memberi izin mengubah data sensitif; fitur mendatang tetap memerlukan policy dan query tenant yang benar. UI shell F07 dijelaskan pada checklist berikutnya.

**EN:** F06 proves isolation of the existing workshop context/dashboard, not future operational modules. Valid membership alone is not permission for sensitive mutations; future features still require policies and scoped queries. The F07 UI shell has its own checklist below.

### F07 laptop follow-up / Pemeriksaan laptop F07

**ID:** F07 memperbarui tampilan; progres persiapan dan hasil terdahulu di atas tidak dihapus atau dianggap sebagai bukti UI F07. Tidak ada migration atau dependency baru. Setelah F07 masuk `main`, simpan perubahan lokal lalu perbarui checkout/aset. Jangan menimpa `.env` atau mengganti `APP_KEY` yang sudah digunakan.

**EN:** F07 changes the UI; earlier preparation/results above are preserved and do not verify F07. No migrations or dependencies are added. After the merge, preserve local work and update code/assets without overwriting `.env` or regenerating an existing application key.

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
php artisan config:clear
npm.cmd run build
```

- [ ] Login akun dengan membership valid: terlihat sidebar **Ringkasan/Pilih bengkel lain**, header bengkel aktif, sapaan, serta role Pemilik/Kasir/Mekanik sesuai membership. / A member sees the shell, verified workshop name, welcome, and actual role.
- [ ] Jika punya trial, status/sisa hari sesuai data bengkel. Tanggal memakai timezone bengkel; sisa sebagian hari dibulatkan ke atas. Tidak perlu menunggu 14 hari atau mengubah waktu/data aplikasi untuk tes kedaluwarsa—suite sudah memeriksa batasnya. / Verify displayed real trial data; automated tests cover exact expiry without altering application time/data.
- [ ] Bengkel tanpa subscription menampilkan **Belum ada paket**, tanpa trial/angka buatan. Tidak ada menu pelanggan, order, stok, invoice, atau tombol pembayaran yang belum diimplementasikan. / Empty subscriptions have an explicit state; no fake business metrics or unimplemented navigation is shown.
- [ ] Ganti bengkel melalui tautan/kartu pemilihan. Nama, role, dan informasi paket mengikuti bengkel yang baru; data bengkel lain tidak tertinggal di tampilan. Gunakan fixture uji sah jika tersedia; jangan memberi privilege pada akun asli untuk menyiapkannya. / Switching updates workshop/role/subscription without stale tenant data; use authorized test fixtures only.
- [ ] Pada HP atau DevTools sekitar 390 px, tombol **Buka navigasi** membuka panel. **Tutup navigasi** dan Escape menutupnya; fokus kembali ke pemicu. Tautan **Ringkasan/Pilih bengkel lain** berfungsi. / Mobile navigation opens/closes with keyboard focus handling and working links.
- [ ] Buka menu pengguna lewat avatar/nama. Nama/email benar; Escape atau klik luar menutup menu. Tombol **Keluar** mengakhiri session; akses dashboard setelah logout harus meminta login. / User details are correct; menu close and POST logout work.
- [ ] Periksa nama panjang dan layout pada desktop/HP: tidak ada scroll horizontal, kartu tidak bertumpuk, teks/tombol terbaca. / Check readable long names and responsive cards without horizontal scrolling.
- [ ] Akun tanpa membership tetap memperoleh halaman **Anda belum tergabung** dalam layout baru, tanpa nama/paket bengkel yang tidak boleh diakses; logout tetap tersedia. / Zero-membership accounts get a safe page in the new shell, with logout and no foreign tenant data.
- [ ] Refresh halaman dan buka dua browser akun A/B terpisah: header/kartu tetap menunjukkan data masing-masing. Tes isolasi F06 di atas tetap relevan. / Refresh and separate A/B sessions retain their own tenant data; F06 isolation checks remain applicable.
- [ ] Opsional pada database tes terpisah yang diverifikasi bagian 6: `php artisan test --filter=ApplicationShellTest`, lalu suite penuh. Baseline F07: **149 tes / 671 assertion**. Jangan jalankan tes `RefreshDatabase` terhadap data aplikasi. / Use the verified dedicated testing database for the optional suite.
- [ ] Catat hasil F07 secara terpisah pada tabel di bawah. Jangan mengubah centang/laporan terdahulu berdasarkan tes cloud F07. / Record F07 results separately, without inferring earlier local outcomes from cloud checks.

| Pemeriksaan F07 / F07 check | Hasil laptop / Laptop result |
| --- | --- |
| Tanggal + commit F07 / Date + commit | Belum diisi / Pending |
| Desktop + navigasi / Desktop + navigation | Belum diuji / Not tested |
| HP + fokus/menu / Mobile + focus/menu | Belum diuji / Not tested |
| Trial/paket kosong + ganti tenant / Trial/empty + switching | Belum diuji / Not tested |
| Menu pengguna + logout / User menu + logout | Belum diuji / Not tested |
| Kasus tanpa membership / No-membership case | Belum diuji / Not tested |
| Suite lokal F07 (opsional) / Local F07 suite (optional) | Belum dijalankan / Not run |

**ID:** Perubahan ini tidak mengubah SMTP/reset email atau mengaktifkan billing/trial enforcement. Catatan pengujian lama dipertahankan; item SMTP yang belum pasti tetap perlu dikonfirmasi sesuai hasil nyata. F08 berikutnya masih diperlukan untuk review Foundation.

**EN:** F07 does not change SMTP/reset email or enable billing/trial enforcement. Existing reports are preserved; uncertain SMTP outcomes still need confirmation from actual results. F08 Foundation review remains next.

## 7. Record results / Catat hasil

**ID:** Isi setelah pengujian; jangan tandai lolos hanya karena sudah pull. Jika gagal, catat langkah, hasil yang diharapkan, hasil aktual, dan pesan error yang sudah disensor. Checklist yang belum dijalankan tetap kosong.

**EN:** Fill this in after testing, not merely after pulling. For failures, record the action, expected/actual result, and a redacted error. Leave untested boxes unchecked.

| Item | Hasil / Result |
| --- | --- |
| Tanggal + penguji / Date + tester | 2026-11-06 — Codex, persiapan terminal/HTTP saja; penguji browser belum diisi / Terminal/HTTP preparation only; browser tester pending |
| Commit lokal (`git rev-parse --short HEAD`) | `5faf95f` — F06 |
| PHP / PostgreSQL / browser | PHP Herd 8.4.26; PostgreSQL CLI 18.6; browser belum dicatat / Browser pending |
| Service dan database / Services and database | Herd + PostgreSQL aktif; IIS dihentikan sementara; koneksi aplikasi lokal berhasil / Running; IIS temporarily stopped; local connection verified |
| Migration / Migrations | 8/8 Ran; termasuk F03–F05, tanpa reset database / Includes F03–F05, without database reset |
| Build + HTTP | Build lolos; `/`, `/login`, `/register`, CSS login HTTP 200 via Nginx / Build and HTTP checks passed |
| Instalasi dependency ulang / Dependency reinstallation | `composer install` / `npm.cmd ci` belum dijalankan ulang pada checkout F06 / Not rerun for this F06 checkout |
| Aplikasi dan autentikasi di browser / Browser app + authentication | Belum diuji pada versi F06 ini / Not tested on this F06 version |
| Email nyata atau sandbox? / Real inbox or sandbox? | Belum diisi / Pending |
| Email reset diterima + waktu / Reset delivery + time | Belum diuji / Not tested |
| Reset dari tautan email / Reset through emailed link | Belum diuji / Not tested |
| Suite lokal (opsional) / Local suite (optional) | Suite F06 belum dijalankan; hasil F02 sebelumnya bukan verifikasi F06 / F06 suite not run; earlier F02 results do not verify F06 |
| Kendala yang perlu diperbaiki / Issues | Konflik IIS/Herd sudah ditangani sementara; hasil tes browser/SMTP masih menunggu / IIS conflict temporarily resolved; browser/SMTP results pending |

**ID:** Task roadmap setelah F07 adalah **F08 — Foundation Test & Hardening**, tetap menunggu instruksi tersendiri. Checklist laptop/SMTP F02 di atas tetap perlu dilakukan saat ada waktu. Tag milestone menyimpan versi kode; bukan backup database atau `.env`.

**EN:** After F07, the next task is **F08 — Foundation Test & Hardening**, requiring a separate instruction. Complete the outstanding F02 laptop/SMTP checklist when time permits. Milestone tags preserve code, not databases or `.env` files.
