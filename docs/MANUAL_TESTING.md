# BengkelOS — Manual Testing / Pengujian Mandiri

> Reviewed / Ditinjau: 2026-10-28  
> Baseline: `main` after / setelah PR #1, tag `f02-authentication-v1`  
> Laptop status / Status laptop: user reported pulling only; checks below are unverified. / User melaporkan baru pull; pemeriksaan di bawah belum dikonfirmasi.

## 1. Current stage / Tahap sekarang

| Tahap / Stage | Status |
| --- | --- |
| F01 — Laravel bootstrap | Selesai / Complete; tag `f01-bootstrap-v1` |
| F02 — Authentication | Sudah di-merge ke `main` / Merged into `main`; tag `f02-authentication-v1` |
| F02 — Laptop + real email verification / Verifikasi laptop + email nyata | Belum dikonfirmasi / Not confirmed |
| F03 — Workshop Tenant | Belum dimulai / Not started; next roadmap task / task roadmap berikutnya |
| F04–F08 | Belum diimplementasikan / Not implemented |

**ID:** F02 menyediakan registrasi akun, login/ingat saya, logout, lupa/reset kata sandi, dan halaman akun `/dashboard`. Registrasi belum membuat workshop, role, atau trial. Ini masih Phase 1 — Foundation, belum seluruh foundation selesai. Dokumen ini bukan instruksi melanjutkan F03.

**EN:** F02 provides account registration, login/remember-me, logout, forgot/reset password, and a protected `/dashboard` account page. Registration does not yet create workshops, roles, or trials. Phase 1 — Foundation is still in progress. This checklist does not authorize F03 implementation.

## 2. Evidence already available / Yang sudah diverifikasi

**ID:** Saat F02 dikerjakan pada 27 Oktober, pemeriksaan berikut lolos di lingkungan cloud terpisah. Ini bukan bukti bahwa konfigurasi laptop atau SMTP pribadi sudah benar.

**EN:** These checks passed in an isolated cloud environment on October 27. They do not verify the laptop configuration or personal SMTP service.

- PHPUnit: **41 tests / 241 assertions**, PHP 8.4 + PostgreSQL 17.
- `vendor/bin/pint --test`, `composer validate --strict`, `php artisan view:cache`, `npm run build`.
- Browser desktop 1280×900 dan HP / mobile 390×844: register, logout, login, salah password / invalid password, submit lupa password / forgot-password submission; tanpa error JavaScript atau halaman melebar / no JavaScript errors or horizontal overflow.
- Tes otomatis / automated tests: validasi, CSRF, session, throttling, pembatasan tamu / guest restrictions, XSS escaping, URL reset, token salah/kedaluwarsa/lintas akun/dipakai ulang / invalid, expired, cross-account, and reused tokens.

**ID:** Yang belum diperiksa: aplikasi pada laptop setelah pull, email reset sampai ke kotak masuk nyata, tautan tersebut membuka aplikasi lokal yang benar, dan keseluruhan reset melalui email nyata. Form lupa password yang menampilkan pesan sukses belum membuktikan email diterima; tes cloud memakai notifikasi simulasi.

**EN:** Outstanding checks: the pulled application on the laptop, delivery to a real inbox, the received link opening the correct local application, and the complete real-email reset flow. A successful forgot-password form response does not prove delivery; cloud tests used fake notifications.

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

- [ ] `main` sudah diperbarui dan kedua tag terlihat. / `main` is updated and both milestone tags are visible.
- [ ] PHP 8.4+ tersedia. Jika tidak terdeteksi, ikuti petunjuk PATH Herd di [README](../README.md#local-setup--persiapan-lokal). / PHP 8.4+ is available; use the README Herd PATH instructions if necessary.
- [ ] `.env` lokal masih ada; pertahankan `APP_KEY` dan kredensial yang sudah digunakan. Jangan menimpa `.env` dengan `.env.example` atau membuat ulang key yang sudah ada. / Preserve the existing `.env`, `APP_KEY`, and credentials; do not overwrite them or regenerate an existing application key.
- [ ] PostgreSQL lokal berjalan dan `DB_*` menunjuk database aplikasi lokal. / Local PostgreSQL is running and `DB_*` targets the local application database.
- [ ] `APP_URL` cocok dengan alamat yang dibuka, biasanya `http://bengkel-saas.test`. Jangan tambahkan `/login` pada `APP_URL`. / `APP_URL` matches the actual application origin, normally `http://bengkel-saas.test`, without `/login`.

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate
npm.cmd run build
```

**ID:** F02 tidak menambah migration; bila tiga migration standar F01 sudah berjalan, tidak ada migration baru. Jangan jalankan `migrate:fresh` atau `migrate:refresh` pada database aplikasi yang berisi data. Gunakan Herd lalu buka `http://bengkel-saas.test`; jika memakai `php artisan serve`, sesuaikan `APP_URL` dengan alamat server tersebut.

**EN:** F02 adds no migrations; an up-to-date F01 database has nothing new to migrate. Never run `migrate:fresh` or `migrate:refresh` against application data. Open the Herd URL, or match `APP_URL` to the server address when using `php artisan serve`.

- [ ] Perintah di atas berhasil, halaman `/` memiliki CSS, dan tidak ada error 500 atau Vite manifest hilang. / Commands succeed; `/` has styling without HTTP 500 or a missing Vite manifest.

## 4. Browser checklist / Checklist browser

**ID:** Centang hanya setelah dicoba di laptop. Gunakan jendela biasa dan incognito sebagai dua session terpisah. Tunggu 60 detik bila terkena throttling sebelum melanjutkan pengujian berikutnya.

**EN:** Check an item only after verifying it on the laptop. Use normal and private windows as separate sessions. Allow 60 seconds after throttling before continuing other checks.

| Selesai / Done | Langkah / Action | Hasil yang diharapkan / Expected result |
| --- | --- | --- |
| [ ] | Sebagai tamu, buka `/dashboard`. / Open `/dashboard` as a guest. | Dialihkan ke `/login`. / Redirects to login. |
| [ ] | Daftar di `/register` dengan email uji dan password minimal 12 karakter; konfirmasi sama. / Register a test account with a matching password confirmation. | Masuk ke `/dashboard`, nama tampil. / Signed in; account name displayed. |
| [ ] | Keluar, lalu coba daftar dengan email yang sama; coba juga password pendek atau konfirmasi berbeda. / Log out; try duplicate registration, a short password, and mismatched confirmation. | Pesan validasi tampil; akun duplikat tidak dibuat. / Validation messages; no duplicate account. |
| [ ] | Login dengan email terdaftar dan password salah, lalu password benar. / Try an incorrect password, then the correct one. | Yang salah ditolak; yang benar masuk ke akun. / Incorrect credentials rejected; correct ones accepted. |
| [ ] | Klik **Keluar**, lalu ketik `/dashboard` dan refresh. / Log out, navigate to `/dashboard`, and refresh. | Harus login lagi; jangan menilai dari halaman lama di browser cache. / Login required; do not judge by a cached page. |
| [ ] | Login dengan **Ingat saya**, tutup seluruh browser tanpa logout, buka kembali. / Remember-me login, close the browser without logging out, and reopen. | Masih dikenali jika cookie disimpan; logout tetap mengakhiri akses. Mode incognito/pembersihan cookie tidak cocok untuk tes ini. / Remembered when cookies persist; logout ends access. Avoid private mode or cookie-clearing settings. |
| [ ] | Sebagai tamu, kirim 5 login gagal dengan email yang sama, lalu coba password benar sebelum 60 detik. / Make 5 failed logins for one email; try the correct password within 60 seconds. | Diblokir sementara; setelah masa tunggu bisa login. / Temporarily blocked; login works after the wait. |
| [ ] | Periksa form pada lebar sekitar 390 px lewat DevTools atau HP. / Check forms at approximately 390 px using DevTools or a phone. | Field/tombol terbaca, tidak perlu scroll horizontal. / Readable fields/buttons; no horizontal scrolling. |

## 5. Real reset email — priority / Email reset nyata — prioritas

**ID:** Ini bagian utama yang belum diverifikasi kemarin. SMTP sandbox hanya membuktikan email diterima sandbox. Untuk bukti sampai kotak masuk nyata, gunakan penyedia SMTP dan email yang Anda kuasai. Jangan menyalin password SMTP atau tautan/token reset ke GitHub, screenshot publik, atau checklist ini.

**EN:** This is the main unverified item. An SMTP sandbox proves sandbox capture only. Verify real inbox delivery using an SMTP provider and an address you control. Do not publish SMTP credentials or reset links/tokens in GitHub, screenshots, or this checklist.

- [ ] Di `.env`, isi `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, dan `MAIL_SCHEME` sesuai petunjuk penyedia bila diperlukan. Jangan gunakan `MAIL_MAILER=log`. / Configure SMTP and its required scheme using provider instructions; do not log reset emails.
- [ ] Jalankan `php artisan config:clear` setelah perubahan konfigurasi; mulai ulang server dev jika dipakai. `.env` lama tidak otomatis diperbarui saat pull. / Clear configuration and restart a dev server if applicable; pulling does not update an existing `.env`.
- [ ] Logout, buka `/forgot-password`, kirim email akun uji yang benar-benar bisa diperiksa. / Log out and request a reset for the test account's accessible email.
- [ ] Email benar-benar diterima di inbox/spam, bukan hanya pesan sukses di website. Catat waktu penerimaan, tanpa token. / Confirm inbox/spam delivery, not just a website success message; record delivery time without the token.
- [ ] Tautan email memakai `APP_URL` yang benar dan membuka form reset di laptop. Domain `.test` lokal tidak otomatis tersedia di HP/perangkat lain. / The emailed link uses the correct `APP_URL` and opens the laptop's reset form; local `.test` domains are not automatically accessible on other devices.
- [ ] Isi password baru dan konfirmasinya; setelah berhasil diarahkan ke login. Password lama gagal, password baru berhasil. / Reset with a matching new password; return to login; old password fails and the new one works.
- [ ] Logout lalu buka tautan yang sama dan coba submit lagi: harus ditolak. Form yang masih bisa dibuka bukan berarti token masih dapat dipakai. / Log out and resubmit the same link: it must fail; rendering a form does not prove token validity.
- [ ] Jika ada session akun yang sama di browser kedua sebelum reset, refresh `/dashboard` sesudah reset: harus diminta login lagi. / Refresh an existing session in a second browser after reset; it must require login again.
- [ ] Minta reset memakai email tidak terdaftar: pesan publik sama, tidak mengungkap apakah akun ada. / Unknown emails receive the same public response, without account disclosure.

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

- [ ] Suite dan pemeriksaan di atas lolos. Baseline F02: 41 tes / 241 assertion. / Checks pass; F02 baseline is 41 tests / 241 assertions.

## 7. Record results / Catat hasil

**ID:** Isi setelah pengujian; jangan tandai lolos hanya karena sudah pull. Jika gagal, catat langkah, hasil yang diharapkan, hasil aktual, dan pesan error yang sudah disensor. Checklist yang belum dijalankan tetap kosong.

**EN:** Fill this in after testing, not merely after pulling. For failures, record the action, expected/actual result, and a redacted error. Leave untested boxes unchecked.

| Item | Hasil / Result |
| --- | --- |
| Tanggal + penguji / Date + tester | Belum diisi / Pending |
| Commit lokal (`git rev-parse --short HEAD`) | Belum diisi / Pending |
| PHP / PostgreSQL / browser | Belum diisi / Pending |
| Aplikasi dan autentikasi lokal / Local app + authentication | Belum diuji / Not tested |
| Email nyata atau sandbox? / Real inbox or sandbox? | Belum diisi / Pending |
| Email reset diterima + waktu / Reset delivery + time | Belum diuji / Not tested |
| Reset dari tautan email / Reset through emailed link | Belum diuji / Not tested |
| Suite lokal (opsional) / Local suite (optional) | Belum dijalankan / Not run |
| Kendala yang perlu diperbaiki / Issues | Belum dilaporkan / Not reported |

**ID:** Setelah hasil laptop dikonfirmasi, task roadmap berikutnya adalah **F03 — Workshop Tenant**. F03 dan tahap lain tetap memerlukan instruksi tersendiri. Dua tag milestone menyimpan versi kode; keduanya bukan backup database atau `.env`.

**EN:** After confirming laptop results, the next roadmap task is **F03 — Workshop Tenant**. Further implementation requires a separate instruction. The two milestone tags preserve code versions, not database or `.env` backups.
