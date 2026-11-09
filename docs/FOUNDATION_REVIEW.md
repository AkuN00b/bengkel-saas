# F08 — Foundation Review / Review Foundation

> Date / Tanggal: 2026-11-09
> Scope / Cakupan: F01–F08; Laravel 13.38.0, PHP 8.4.27, PostgreSQL 17.
> Code milestone / Milestone kode: `f08-foundation-hardening-v1`.

**EN:** Foundation code validation passes for the implemented authentication, owner provisioning, memberships, workshop context, subscription schema, and application shell. The full suite passes **161 tests / 703 assertions**. Existing HTTP tenant boundaries are verified. This review closes F08; it does not authorize Sprint 1 or certify a production deployment.

**ID:** Validasi kode Foundation lolos untuk autentikasi, provisioning owner, membership, konteks bengkel, schema subscription, dan shell aplikasi yang tersedia. Suite penuh lolos **161 tes / 703 assertion**. Boundary tenant HTTP yang sudah ada terverifikasi. Review ini menutup F08; bukan izin melanjutkan Sprint 1 atau sertifikasi deployment production.

## Findings and changes / Temuan dan perubahan

### Default seeding / Seeder default

**EN:** The previous default seeder created `test@example.com` using the factory's predictable password, and failed on repeated execution or an existing matching account. Two regression tests reproduced these failures. The default seeder now creates no users or tenant data and leaves existing accounts unchanged. Plan catalog initialization stays in its existing migration. Owners use the normal registration flow. Test factories remain available only for explicit testing/development use.

**ID:** Seeder default sebelumnya membuat `test@example.com` dengan password bawaan factory, dan gagal saat diulang atau sudah ada akun dengan email tersebut. Dua tes regresi mereproduksi kegagalan ini. Seeder default kini tidak membuat user/data tenant dan tidak mengubah akun yang tersedia. Inisialisasi katalog paket tetap pada migration lama. Owner menggunakan alur registrasi normal. Factory tetap tersedia untuk pemakaian eksplisit pada tes/development.

**EN:** Updating code does not delete accounts created by earlier seeds. If an old seed was used, review those accounts before using that environment with real data. Establish account provenance before changing/removing it; matching the example email alone is insufficient.

**ID:** Pembaruan kode tidak menghapus akun hasil seed lama. Jika pernah memakai seed lama, tinjau akun tersebut sebelum environment digunakan dengan data nyata. Pastikan asal akun sebelum mengganti/menghapusnya; kecocokan email contoh saja tidak cukup.

### Test database protection / Pengaman database tes

**EN:** PHPUnit environment defaults can be overridden by shell variables or cached application configuration. `Tests\TestCase::createApplication()` now validates resolved configuration before `RefreshDatabase` runs: environment `testing`, default connection/driver `pgsql`, database exactly `bengkel_saas_testing`, and empty `DB_URL`. Eight unit cases cover accepted configuration and unsafe database/URL/driver/environment overrides. Rejected values are not echoed, so credentials are not exposed by the guard.

**ID:** Nilai default environment PHPUnit dapat ditimpa variabel shell atau cache konfigurasi aplikasi. `Tests\TestCase::createApplication()` kini memvalidasi konfigurasi hasil resolusi sebelum `RefreshDatabase`: environment `testing`, connection/driver default `pgsql`, database tepat `bengkel_saas_testing`, dan `DB_URL` kosong. Delapan kasus unit mencakup konfigurasi sah dan override database/URL/driver/environment tidak aman. Nilai yang ditolak tidak dicetak agar guard tidak membocorkan kredensial.

**EN:** Two actual PHPUnit subprocess probes targeted a disposable database through an inherited database name and through `DB_URL`. Both stopped at the guard with zero assertions, and a sentinel row in that database survived. A matching name is still not proof that a host is disposable: verify host/role and keep application data out of the testing database. The guard applies to this project's feature-test base, not arbitrary Artisan commands or future tests that bypass that base.

**ID:** Dua probe subprocess PHPUnit menargetkan database sementara melalui nama database dari environment dan melalui `DB_URL`. Keduanya berhenti pada guard dengan nol assertion, dan baris penanda di database itu tetap ada. Nama yang cocok tetap bukan bukti host aman dihapus: verifikasi host/role dan jangan simpan data aplikasi di database tes. Guard berlaku pada base feature test proyek ini, bukan sembarang perintah Artisan atau tes baru yang melewati base tersebut.

### Session regressions / Regresi session

**EN:** Added real login/registration request regressions for a password change before the first protected page. Both already passed on the baseline: Laravel's session guard stores the password fingerprint when login occurs, and `auth.session` rejects the changed password. No custom session hashing/listener was necessary. Existing reset-token, remember-token, session-rotation, and invalidation checks remain passing.

**ID:** Ditambahkan regresi request login/registrasi nyata untuk perubahan password sebelum halaman terlindungi pertama. Keduanya sudah lolos pada baseline: session guard Laravel menyimpan fingerprint password saat login, dan `auth.session` menolak password yang berubah. Tidak diperlukan hashing/listener session tambahan. Tes token reset, remember token, rotasi session, serta pencabutan session yang sudah ada tetap lolos.

### Dependency patches / Patch dependency

**EN:** The initial Composer audit reported seven advisories affecting four locked Symfony packages. Only these four packages were patched within the existing constraints; no new dependency or major upgrade was introduced. The final Composer audit and npm audit report zero known advisories/vulnerabilities at review time.

**ID:** Audit Composer awal melaporkan tujuh advisory pada empat paket Symfony yang terkunci. Hanya empat paket ini yang diperbarui sesuai constraint yang tersedia; tidak ada dependency baru atau upgrade major. Audit Composer dan npm akhir melaporkan nol advisory/kerentanan yang diketahui pada waktu review.

| Package / Paket | Before / Sebelum | After / Sesudah | Initial advisory IDs / ID advisory awal |
| --- | --- | --- | --- |
| `symfony/http-foundation` | 8.1.9 | 8.1.11 | PKSA-24ph-xkrh-y79j; PKSA-pr6q-2w5s-kzzf; PKSA-nxjs-s6hn-vb6m; PKSA-b2gg-vk1g-6d5q |
| `symfony/http-kernel` | 8.1.9 | 8.1.11 | PKSA-3gxv-7xhq-mgmz |
| `symfony/mime` | 8.1.7 | 8.1.10 | PKSA-wtxr-p26d-nn42 |
| `symfony/routing` | 8.1.9 | 8.1.11 | PKSA-y8c5-1b7f-r1gj |

## Validation evidence / Bukti validasi

**EN:** Checks used disposable PostgreSQL databases, with PHP running in temporary containers. Docker remains optional and is not part of the required application stack. Destructive migration checks were restricted to the isolated migration database.

**ID:** Pemeriksaan memakai database PostgreSQL sementara, dengan PHP pada container sementara. Docker tetap opsional dan bukan bagian stack aplikasi yang diwajibkan. Pemeriksaan migration yang membangun ulang schema hanya dilakukan pada database migration terpisah.

| Check / Pemeriksaan | Result / Hasil |
| --- | --- |
| `php artisan test --compact` after dependency patches / setelah patch dependency | PASS — 161 tests / tes; 703 assertions / assertion |
| Existing auth/provisioning regressions / Regresi autentikasi/provisioning | PASS — validation, CSRF, rate limits, reset tokens, mass assignment, complete owner/trial creation, transaction rollback / validasi, CSRF, rate limit, token reset, mass assignment, owner/trial lengkap, rollback transaksi |
| Existing roles and tenant tests / Tes role dan tenant | PASS — per-workshop roles, no platform-admin membership bypass, A/B isolation, tampered IDs/session, revoked membership, switching, scoped subscription reads / role per bengkel, admin tetap perlu membership, isolasi A/B, manipulasi ID/session, pencabutan membership, pergantian, subscription sesuai tenant |
| Database constraints / Constraint database | PASS — unique/FK/check constraints, required roles, valid subscription status/dates and at most one stored trialing/active subscription per workshop / unique/FK/check, role wajib, status/tanggal subscription valid dan maksimal satu subscription tersimpan trialing/active per bengkel |
| `php artisan migrate:fresh --seed --force` on disposable migration database only / hanya pada database migration sementara | PASS — 8 migrations, 3 plans, 0 users / 8 migration, 3 paket, 0 user |
| Repeated `db:seed`, full `migrate:reset`, then `migrate --seed` / Seed berulang, rollback penuh, lalu migration ulang | PASS |
| Unsafe configuration subprocesses / Subprocess konfigurasi tidak aman | PASS — rejected before schema refresh; sentinel preserved / ditolak sebelum refresh schema; penanda tetap ada |
| `vendor/bin/pint --test` | PASS |
| `composer validate --strict` | PASS |
| `composer audit --locked` / `npm audit` | PASS — no known advisories / tidak ada advisory diketahui |
| `php artisan view:cache` | PASS |
| `npm run build` | PASS |
| Chromium 1280×900 and 390×844 / desktop dan HP | PASS — registration, real trial display, A/B isolation, forged activation IDs (404), missing CSRF (419), selection, mobile dialog Escape/focus, logout, invalid/valid login; no JS errors/horizontal overflow / registrasi, tampilan trial nyata, isolasi A/B, ID aktivasi palsu, CSRF hilang, pemilihan, Escape/fokus dialog, logout, login salah/benar; tanpa error JS/scroll horizontal |

## Update and remaining checks / Pembaruan dan pemeriksaan tersisa

**EN:** Follow the [F08 laptop checklist](MANUAL_TESTING.md#f08-laptop-follow-up--pemeriksaan-laptop-f08): pull, `composer install`, clear configuration, check migration status, and rebuild assets. F08 adds no migrations. Do not run fresh/reset migrations on application data. Existing `.env`, `APP_KEY`, and manual results are preserved. If test startup rejects configuration, fix `.env.testing`, shell overrides, or cached configuration; retain the guard.

**ID:** Ikuti [checklist laptop F08](MANUAL_TESTING.md#f08-laptop-follow-up--pemeriksaan-laptop-f08): pull, `composer install`, bersihkan konfigurasi, periksa status migration, dan build aset. F08 tidak menambah migration. Jangan menjalankan fresh/reset migration pada data aplikasi. `.env`, `APP_KEY`, dan hasil manual lama dipertahankan. Jika startup tes menolak konfigurasi, benahi `.env.testing`, override shell, atau cache konfigurasi; pertahankan guard.

**EN:** Cloud tests do not confirm the user's current laptop flows or real SMTP delivery. Earlier checkmarks and pending report entries are preserved; the tester should reconcile them with a dated version-specific result. Password notifications were simulated in feature tests; browser checks did not send real email. Deployment still needs its own HTTPS, secrets, `APP_DEBUG=false`, database permissions, and backup/restore validation. Git tags preserve code and do not back up database contents or `.env`.

**ID:** Tes cloud tidak mengonfirmasi alur laptop terkini atau pengiriman SMTP nyata milik user. Centang terdahulu dan entri laporan tertunda dipertahankan; penguji perlu menyelaraskannya dengan hasil bertanggal dan versi yang diuji. Notifikasi password disimulasikan pada feature test; tes browser tidak mengirim email nyata. Deployment tetap memerlukan validasi HTTPS, secret, `APP_DEBUG=false`, izin database, dan backup/restore sendiri. Tag Git menyimpan kode dan tidak membackup isi database atau `.env`.

**EN:** Tenant isolation is proven for the current dashboard and workshop-selection endpoints, not modules that do not exist yet. Future sensitive mutations still require role authorization, scoped queries, validation, and cross-tenant tests. Subscription display does not enforce trial/billing access. **STOP after F08; C01 needs an explicit instruction.**

**ID:** Isolasi tenant terbukti untuk dashboard dan endpoint pemilihan bengkel saat ini, bukan modul yang belum dibuat. Mutasi sensitif berikutnya tetap memerlukan otorisasi role, query tenant, validasi, dan tes lintas tenant. Tampilan subscription tidak menerapkan pembatasan akses trial/billing. **BERHENTI setelah F08; C01 memerlukan instruksi eksplisit.**
