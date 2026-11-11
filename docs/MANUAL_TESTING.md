# BengkelOS — Manual Testing / Pengujian Mandiri

> Reviewed / Ditinjau: 2026-11-11

**ID — Versi terbaru Sprint 5:** Dashboard D01 dan laporan D02 tersedia. Mulai dari checklist ini. Manual Sprint 2–5 tetap belum dikonfirmasi; hasil otomatis bukan centang penerimaan user. Bagian Sprint 2–4 di bawah adalah arsip pemeriksaan rilis sebelumnya.

**EN — Latest Sprint 5 version:** D01 dashboards and D02 reports are available. Start here. Sprint 2–5 human acceptance remains unconfirmed; automated evidence does not check human-acceptance boxes. Older sprint sections preserve previous release evidence.

## Sprint 5 — Dashboard dan Laporan

**ID:** Nyalakan Herd dan PostgreSQL, buka `http://bengkel-saas.test`, lalu login sesuai role. Build sudah tersedia; tidak perlu npm run dev. Di laptop lain setelah menerima rilis, jalankan dari folder project:

**EN:** Run Herd/PostgreSQL and log in with the appropriate role. Compiled assets do not require a development server. On another laptop after receiving this release, run from the project folder:

```powershell
git switch main
git pull --ff-only origin main
git fetch origin --tags
git show --no-patch --oneline sprint-5-dashboard-reports-v1
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

**ID:** Jangan jalankan migrate:fresh/reset/rollback/seed ulang pada database aplikasi. Hanya satu migration indeks baru; tidak ada tabel transaksi baru. Semua angka memakai catatan nyata, sehingga bengkel tanpa transaksi menampilkan nol.

**EN:** Never reset, roll back, or reseed application data. This release adds one index migration and no transaction tables. Empty workshops correctly show zero rather than sample figures.

### Automated Sprint 5 evidence / Bukti otomatis Sprint 5

- [x] **419 tes / 2.732 assertion** pada suite penuh lolos, termasuk role/tenant/ID, membership dicabut/nonaktif, semua cutoff historis, koreksi lintas hari, snapshot master/retur, input tanggal, agregat di atas PHP_INT_MAX, dan perlindungan formula CSV. / Full suite passed **419 tests / 2,732 assertions**, including security, financial/time, exact-money, and snapshot regression cases.
- [x] Proses PHP terpisah membuktikan REPEATABLE READ READ ONLY: pembacaan kedua tetap sama saat pembayaran/koreksi committed oleh proses lain; penulisan di transaksi laporan ditolak dan transaksi operasional tetap berjalan. / Independent-process tests verified consistent read-only snapshots during concurrent committed payment/reversal writes.
- [x] Ekspor **205 WO** melewati batch 200 tanpa terpotong; pagination dan query history 5 vs 15 record tidak menambah query per baris. Migration indeks turun-naik mempertahankan record di database testing; EXPLAIN membuktikan kelayakan kelima indeks. / Streaming, pagination, N+1 checks, index round trips, and ordered-index eligibility passed on the disposable testing database.
- [x] **3 tes JavaScript**, Pint seluruh project, Composer validation strict, kompilasi Blade, dan build Vite lolos. / **3 JavaScript tests**, whole-project Pint, strict Composer validation, Blade compilation, and Vite build passed.
- [x] Edge desktop **1280×900** dan HP **390×844**: dashboard ketiga role, seluruh laporan, input cutoff/kronologi, CSV, A4/PDF, riwayat kendaraan, ID tenant, konteks bengkel lama, akses kasir, dan tugas mekanik lolos. Tidak ada page error JavaScript atau overflow halaman. / All-role desktop/mobile browser checks and CSV/A4/PDF passed without page errors or horizontal page overflow.
- [x] Migration lokal baru Ran, total **15 migration**; semua jumlah record aplikasi sebelum/sesudah tetap sama. Tidak ada reset/seed database aplikasi. Herd/Nginx menyajikan BengkelOS HTTP 200. / All 15 local migrations ran with pre-existing counts unchanged, no application reseeding/reset, and the Herd/Nginx app responding successfully.

**ID:** Browser memakai server terpisah `127.0.0.1:8011`, akun sintetis, `bengkel_saas_testing`, dan mail array. CSV/PDF uji berisi data sintetis. Server uji dihentikan dan file kredensial sementara dihapus. Hasil ini tidak membuktikan printer fisik, SMTP nyata, atau penerimaan manual user.

**EN:** Browser verification used an isolated server, synthetic accounts/test database, and array mail. Test CSV/PDF files contain synthetic data. The test server was stopped and temporary credential file removed. Physical printing, real SMTP, and human acceptance remain separate.

### User acceptance Sprint 5 / Penerimaan user Sprint 5

**ID:** Kotak berikut sengaja kosong sampai lu mencoba dan mengonfirmasi. Gunakan akun/data uji yang mudah dikenali. Pembayaran/koreksi adalah pencatatan aplikasi, bukan transfer uang. Untuk skenario lintas hari, gunakan catatan pada hari berbeda; tidak ada form untuk memundurkan timestamp server.

**EN:** Leave these unchecked until human confirmation. Use recognizable test accounts/data. Payment entries and corrections record transactions rather than moving money. Cross-day scenarios require records on different days; server timestamps cannot be backdated through the UI.

- [ ] Owner: Dashboard menampilkan Draft/Antre/Dikerjakan, Selesai hari ini, pembayaran/koreksi/bersih, nilai invoice terbit/batal/bersih, jumlah dan sisa belum lunas, stok menipis, tren tujuh hari, serta tiga daftar lima record. Tautan kartu membuka filter yang sesuai. / Check all owner figures, lists, and links against actual records.
- [ ] Kasir: hanya ringkasan operasional/jumlah belum lunas, tanpa agregat Rupiah. Invoice lama tetap dapat dibuka. Membuka URL laporan/CSV/cetak secara langsung ditolak. / Check cashier operational access and financial report denial.
- [ ] Mekanik: hitungan dan riwayat hanya tugas sendiri; tidak melihat stok/invoice/pembayaran. Mencoba URL laporan atau ekspor riwayat ditolak. / Check mechanic assignment scope and denied financial/export access.
- [ ] Bengkel kosong menampilkan nol/petunjuk. Perbarui memuat data terbaru tanpa polling. Informasi akun/trial masih ada di bawah. / Check empty states, explicit refresh, and retained account details.
- [ ] Laporan pembayaran: cocokkan tunai/transfer/Lainnya, pembayaran yang dikoreksi tetap masuk tanggal asal, koreksi masuk tanggal koreksi. Nilai bersih negatif dijelaskan sebagai koreksi pencatatan, bukan refund. / Reconcile original/correction dates, methods, and negative net receipts.
- [ ] Nilai invoice: cocokkan jasa/spare part/total terbit, batal, dan bersih. Draft/batal draft tidak dihitung; pembayaran sebagian tidak dibagi secara perkiraan. Retur setelah terbit tidak mengubah invoice. / Reconcile snapshot invoice values, drafts, installments, and later returns.
- [ ] Piutang: pilih Sekarang, Akhir hari, dan waktu sampai detik; waktu masa depan ditolak. Cocokkan total daftar dengan rincian pada cutoff yang sama. / Check cutoff controls, future rejection, and summary/detail reconciliation.
- [ ] Telusuri invoice: sebelum terbit tidak ada kejadian; setelah terbit ada sisa; pembayaran mengurangi; koreksi menambah kembali; pembatalan meniadakan piutang. Kejadian sesudah cutoff tidak ditampilkan. / Verify the historical lifecycle, including invoices currently voided.
- [ ] Piutang CSV/cetak tetap memakai waktu pilihan dan menyertakan timezone/waktu dibuat. Periksa nominal dan file di aplikasi pembaca CSV yang lu pakai. / Verify export cutoff metadata and amounts with your CSV reader.
- [ ] Cetak A4/simpan PDF: ringkasan seluruh filter, rincian hanya halaman aktif, dan keterangannya terlihat. Coba printer fisik bila tersedia. / Verify page scope, PDF, and physical printing where available.
- [ ] Riwayat servis: filter status/tanggal dibuat/selesai/mekanik/pelat/nomor WO berjalan. Dari detail kendaraan/pelanggan, Semua waktu tetap berhalaman. Perubahan pelat atau pemilik kendaraan tidak memutus riwayat WO pelanggan lama. / Check history filters, stable identities, pagination, and historical customer ownership.
- [ ] Ganti bengkel, lalu buka filter dari tab lama: pesan muat ulang muncul. Data bengkel lain tidak dapat dibaca lewat ID atau URL yang diubah. / Check stale tenant context and manipulated IDs.
- [ ] HP/desktop nyaman: angka terbaca, tabel dapat digeser di dalam area tabel, halaman tidak melebar, navigasi dan tombol tetap mudah dipakai. / Check real-device usability and local table scrolling.

**ID:** Setelah Sprint 5, berhenti sebelum milestone Alpha: validasi alur dengan bengkel nyata dan perbaikan operasional. Definisi angka dan batas fitur: [Sprint 5](SPRINT_5_DASHBOARD_REPORTS.md).

**EN:** Stop before Alpha real-workshop validation and operational fixes. See [Sprint 5](SPRINT_5_DASHBOARD_REPORTS.md) for definitions and scope.

**ID — Arsip Sprint 4:** B01–B03 invoice dan pembayaran sudah tersedia. [Checklist Sprint 4](#sprint-4--invoice-dan-pembayaran) tetap disimpan. Manual Sprint 2/3/4 tetap belum dikonfirmasi; centang otomatis di bawah hanya hasil pemeriksaan Codex.

**EN — Sprint 4 archive:** B01–B03 invoices/payments are available. The [Sprint 4 checklist](#sprint-4--invoice-dan-pembayaran) preserves prior evidence. Sprint 2/3/4 manual acceptance is unconfirmed; automated checks only record Codex verification.

## Sprint 4 — Invoice dan Pembayaran

**ID:** Nyalakan Herd dan PostgreSQL, buka `http://bengkel-saas.test`. Tidak perlu npm run dev jika build sudah tersedia. Di laptop lain setelah menerima rilis, jalankan dari folder project di PowerShell; jangan migrate:fresh, reset, rollback, atau seed ulang database aplikasi.

**EN:** Run Herd/PostgreSQL and open the local app. Compiled assets do not require npm run dev. On another laptop after receiving the release, run these commands from the project directory; never reset/roll back/reseed application data.

```powershell
git switch main
git pull --ff-only origin main
git fetch origin --tags
git show --no-patch --oneline sprint-4-invoices-payments-v1
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

### Automated local verification / Verifikasi otomatis lokal

- [x] Migration Sprint 4 Ran, total 14 migration. Jumlah record lama tetap: users 4, workshops 2, memberships 2, plans 3, subscriptions 2, serta customers/vehicles/WO/events/catalogs/lines/movements 0. Tidak ada seed/reset data aplikasi. / One new migration ran, 14 total; all pre-existing row counts were preserved without application reseeding/resetting.
- [x] Suite penuh **382 tes / 2.467 assertion** lolos, termasuk regresi, source fingerprint/retur, snapshot, role/tenant, pembayaran/koreksi, constraint/triggers, rollback, serta proses PHP bersamaan untuk issue/pay/reverse/void/retur. / Full suite passed **382 tests / 2,467 assertions**, including financial/security/database and independent-process concurrency checks.
- [x] **3 tes JavaScript**, Pint seluruh project, Composer validation strict, kompilasi Blade, dan build Vite lolos. / **3 JavaScript tests**, project-wide Pint, strict Composer validation, Blade compilation, and Vite build passed.
- [x] Browser Edge desktop **1280×900** dan HP **390×844** lolos: draft jumlah bersih, harga beralasan, stale source/refresh, issue, pembayaran campuran sampai lunas, reversal owner, void/reissue, retur setelah terbit, role/IDOR, konflik versi/bengkel, pagination, navigasi mobile, cetak A4 dan file PDF browser. Tanpa error JavaScript/overflow horizontal. / All-role desktop/mobile and A4/PDF browser checks passed without JS errors or horizontal overflow.
- [x] Herd/Nginx kembali menyajikan BengkelOS HTTP 200 pada `bengkel-saas.test`. IIS W3SVC dihentikan sementara setelah aktif kembali; startup type tidak diubah. / Herd/Nginx serves BengkelOS HTTP 200 after temporarily stopping reactivated IIS; service startup settings were unchanged.

**ID:** Browser memakai server `127.0.0.1:8011`, akun sintetis, database `bengkel_saas_testing`, dan mail array. PDF uji berisi data sintetis. Tidak membuktikan printer fisik, SMTP, atau penerimaan user sudah lolos.

**EN:** Browser automation used a separate server, synthetic accounts/test database, and array mail. Generated PDFs contain synthetic data. This does not confirm physical printers, real SMTP, or user acceptance.

### User acceptance / Penerimaan user

**ID:** Semua kotak ini sengaja kosong sampai lu mencoba dan mengonfirmasi. Gunakan data uji yang mudah dikenali; angka pembayaran adalah pencatatan, bukan transfer uang oleh aplikasi.

**EN:** These boxes remain empty until user confirmation. Use recognizable test records; payment entry records money rather than transferring it.

- [ ] Owner/kasir: dari detail WO Antre/Dikerjakan buat draft. Hanya jasa aktif dan barang Dipakai bersih setelah retur yang disalin; Rencana/batal/retur penuh tidak ditagih. / Check draft source eligibility and net quantities.
- [ ] Ubah harga draft dengan alasan; WO dan katalog tetap sama. Catatan draft bisa diubah. / Verify reasoned overrides and note edits without changing sources.
- [ ] Ubah WO atau catat retur setelah draft dibuat. Penerbitan ditolak sampai Perbarui dari WO; konfirmasi menghapus penyesuaian harga lama dan menyusun ulang rincian. / Check stale-source detection and explicit refresh.
- [ ] Invoice hanya dapat terbit setelah WO Selesai; WO Dibatalkan tidak ditagihkan. Draft tanpa baris ditolak; invoice sah total nol Lunas—tanpa pembayaran. / Verify issuance and zero/empty rules.
- [ ] Terbitkan: nomor INV unik per bengkel, snapshot identitas/rincian terkunci. Mengubah master tidak mengubah invoice terbit. / Check numbering and frozen snapshots.
- [ ] Catat pembayaran sebagian tunai, lanjut transfer atau Lainnya sampai lunas. Metode Lainnya wajib keterangan, nominal tidak boleh nol/negatif/lebih dari sisa. / Check installments, mixed methods, validation, and balance.
- [ ] Kasir tidak melihat tindakan void/reversal; mekanik tidak mengakses daftar/detail/cetak invoice, namun tetap melihat item WO tugas sendiri. / Check financial role boundaries.
- [ ] Owner membatalkan salah catat pembayaran dengan alasan; record lama masih terlihat, sisa tagihan dihitung ulang, dan pembayaran benar dicatat baru. Ini bukan refund uang. / Check record correction and preserved originals.
- [ ] Invoice dengan pembayaran valid tidak dapat dibatalkan. Invoice tanpa pembayaran valid dapat di-void owner, lalu dibuat pengganti dengan nomor baru dan tautan ke invoice lama. / Verify void/reissue boundaries and history.
- [ ] Pembatalan WO membatalkan draft invoice secara atomik tanpa mengubah riwayat stok yang sudah terpakai. / Check linked draft voiding on WO cancellation.
- [ ] Retur sesudah invoice terbit hanya mengubah stok; jumlah/harga/total invoice tidak otomatis berkurang. / Verify post-issue returns do not rewrite billing.
- [ ] Klik ulang/kirim ulang pembayaran yang sama tidak menambah catatan ganda. Form lama setelah perubahan atau pergantian bengkel ditolak dengan konflik jelas. / Check idempotency and stale/context conflicts.
- [ ] Pencarian nomor/WO/pelat/pelanggan, filter status dokumen/pembayaran, serta pagination rincian/payment/event berjalan. / Check searching, filters, and pagination.
- [ ] Cetak A4 atau simpan PDF browser: identitas, rincian, total, pembayaran valid dan sisa benar; Draft/Batal mempunyai penanda. Uji printer fisik bila tersedia. / Check real print/PDF output and document marks.
- [ ] HP/desktop nyaman, tidak melebar; angka Rupiah tepat dan waktu mengikuti timezone bengkel. / Check real-device usability, exact currency display, and timezone.

**ID:** Stop sebelum Sprint 5. Detail keputusan dan hasil: [Sprint 4](SPRINT_4_INVOICES_PAYMENTS.md).

**EN:** Stop before Sprint 5. See [Sprint 4](SPRINT_4_INVOICES_PAYMENTS.md) for decisions/results.

**ID — Versi terbaru Sprint 3:** I01–I05 jasa, spare part, dan stok sudah tersedia. Mulai dari [checklist Sprint 3](#sprint-3--jasa-spare-part-dan-stok). Penerimaan manual Sprint 2 dan Sprint 3 tetap belum dikonfirmasi; hasil otomatis bukan pengganti centang user.

**EN — Latest Sprint 3 version:** I01–I05 services, parts, and inventory are available. Start with the [Sprint 3 checklist](#sprint-3--jasa-spare-part-dan-stok). Sprint 2/3 user acceptance remains unconfirmed; automation does not check user-acceptance boxes.

## Sprint 3 — Jasa, Spare Part, dan Stok

**ID:** Nyalakan Herd dan PostgreSQL, lalu buka `http://bengkel-saas.test`. Build frontend tersedia; tidak perlu npm run dev. Untuk laptop lain setelah menerima rilis, jalankan dari folder project:

**EN:** Run Herd/PostgreSQL and open the local app. Existing compiled assets do not require npm run dev. On another laptop after receiving this release, run from the project directory:

```powershell
git switch main
git pull --ff-only origin main
git fetch origin --tags
git show --no-patch --oneline sprint-3-services-inventory-v1
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

### Automated local verification / Verifikasi otomatis lokal

- [x] Migration Sprint 3 Ran, total 13 migration. Data lama sebelum/sesudah upgrade tetap: users 4, workshops 2, memberships 2, plans 3, subscriptions 2, customers 0, vehicles 0, work_orders 0, work_order_events 0. / One new migration ran, 13 total; existing application record counts were preserved.
- [x] Suite penuh **358 tes / 2.151 assertion** lolos, termasuk regresi Sprint 2, role/tenant, harga dan snapshot, stok/retur, idempotensi, constraint/ledger append-only, rollback/upgrade, CSRF, dan proses PHP bersamaan. / Full suite passed **358 tests / 2,151 assertions**, including Sprint 2 regressions and inventory/security/concurrency checks.
- [x] **3 tes JavaScript**, Pint seluruh project, Composer validation strict, kompilasi Blade, dan build Vite lolos. / **3 JavaScript tests**, project-wide Pint, strict Composer validation, Blade compilation, and Vite build passed.
- [x] Browser Edge otomatis desktop **1280×900** dan HP **390×844**: katalog/pagination, restock, jasa/rencana/pemakaian, perubahan harga, retur parsial/final, pembatalan, seluruh role, ID tenant, form stale/pergantian bengkel, koreksi owner, aktivasi, dan navigasi HP lolos. Tanpa error JavaScript atau overflow horizontal. / Automated desktop/mobile Edge checks passed across catalog, inventory, WO, role/security, conflict, and responsive flows; no JavaScript errors or horizontal overflow.

**ID:** Otomasi memakai `bengkel_saas_testing`, akun sintetis, dan mail array. Database aplikasi tidak di-reset atau di-seed ulang. Angka di atas adalah bukti pemeriksaan lokal 10 November, bukan data contoh yang ditambahkan ke akun user.

**EN:** Automation uses the dedicated test database, synthetic accounts, and array mail. Application data was not reset or reseeded. These counts document November 10 verification, not sample data added to user accounts.

### User acceptance / Penerimaan user

**ID:** Semua kotak berikut tetap kosong sampai lu mencoba dan mengonfirmasi. Pakai data uji yang mudah dikenali. Tidak perlu memakai pgAdmin untuk katalog/stok.

**EN:** Leave these boxes empty until the user performs and confirms the checks. Use recognizable test records; catalog/inventory flows do not require pgAdmin.

- [ ] Owner: tambah jasa dengan nama/deskripsi/harga; ubah harga katalog. Kasir dapat membaca tetapi tidak mengubah; mekanik tidak mempunyai menu katalog. / Verify catalog roles and editing.
- [ ] Owner: tambah spare part dengan SKU, satuan, harga, batas minimum. Saldo awal nol; stok tidak dapat diedit melalui form katalog. Satuan tidak dapat diganti. / Verify part creation, zero opening balance, and immutable units.
- [ ] SKU beda huruf besar/kecil pada bengkel yang sama ditolak; SKU kosong diperbolehkan. Pencarian nama/SKU, filter aktif/nonaktif/stok menipis, dan pagination berjalan. / Check SKU uniqueness, search, filters, and pagination.
- [ ] Owner/kasir: Restock menambah stok dan riwayat. Koreksi masuk/keluar hanya owner, wajib alasan; stok negatif ditolak. / Check stock roles, reasons, balances, and insufficient stock.
- [ ] Pada WO aktif, tambah jasa dari katalog, ubah jumlah/harga WO. Harga dan deskripsi katalog berubah kemudian tetapi snapshot WO tidak ikut berubah. / Check service line overrides and snapshots.
- [ ] Tambah spare part Rencana; stok belum berkurang dan tidak dicadangkan. Konfirmasi tidak tersedia sebelum Dikerjakan. / Planned lines do not consume/reserve stock.
- [ ] Saat Dikerjakan, konfirmasi seluruh jumlah satu baris. Stok berkurang tepat sekali; klik ulang tidak menggandakan movement. / Confirm full-line usage exactly once.
- [ ] Setelah dipakai, identitas/jumlah tidak bisa diubah; owner/kasir masih dapat mengubah harga selama aktif tanpa movement tambahan. / Used identities/quantities lock; active price edits do not move stock.
- [ ] Retur sebagian beberapa kali, maksimal sisa yang dipakai; kelebihan ditolak. Pastikan barang fisik benar-benar kembali dan layak disimpan. / Check partial/repeated usable-stock returns and limits.
- [ ] Retur menaikkan stok, tetapi jumlah asli/harga/subtotal WO tetap; jumlah dipakai/diretur terlihat terpisah. / Returns affect stock, not WO line value.
- [ ] WO dengan sisa Rencana tidak dapat diselesaikan. Batalkan rencana atau konfirmasi pemakaian dahulu; WO tanpa item tetap bisa selesai. / Resolve plans before completion; legacy empty WOs still complete.
- [ ] Batalkan WO dengan barang Dipakai dan Rencana: rencana dibatalkan, stok terpakai tidak otomatis kembali. / Cancellation voids plans without restoring usage.
- [ ] Setelah WO final, owner membuka detail spare part → Riwayat stok → Retur WO final. Retur tercatat tanpa membuka/mengubah WO final; kasir/mekanik tidak boleh menjalankannya. / Verify owner-only separate final returns.
- [ ] Nonaktifkan barang: tidak dapat dipilih/restock/dipakai lagi; riwayat, koreksi owner, dan retur pemakaian lama tetap tersedia. / Check inactive catalog behavior.
- [ ] Mekanik hanya melihat item/harga WO tugas sendiri, dapat mulai/selesai sesuai aturan Sprint 2, tanpa tindakan edit barang/stok/harga. / Check mechanic read-only item access.
- [ ] Dua tab: perubahan katalog/stok/WO membuat form lama ditolak. Ganti bengkel di tab lain: form sebelumnya ditolak dengan konflik jelas. / Check stale versions and workshop switches.
- [ ] HP/desktop: menu, form, pilihan katalog, riwayat, dan angka Rupiah terbaca tanpa melebar; waktu mengikuti timezone bengkel. / Check real-device usability, Rupiah display, and workshop timezones.

**ID:** Aturan tagihan/refund uang belum ditetapkan oleh subtotal/retur ini. Stop sebelum Sprint 4. Detail implementasi: [Sprint 3](SPRINT_3_SERVICES_INVENTORY.md).

**EN:** These subtotals/returns do not define billing/money-refund rules. Stop before Sprint 4. See [Sprint 3](SPRINT_3_SERVICES_INVENTORY.md).

**ID — Versi terbaru Sprint 2:** W01–W03 dan akun mekanik sudah tersedia. Mulai dari [checklist WO dan mekanik](#sprint-2--wo-dan-mekanik). Hasil otomatis, browser sintetis, SMTP asli, dan penerimaan manual user dicatat terpisah. Bagian C02/C03 serta foundation di bawah merupakan catatan historis.

**EN — Latest Sprint 2 version:** W01–W03 and mechanic onboarding are available. Start with the [WO and mechanic checklist](#sprint-2--wo-dan-mekanik). Automation, synthetic browser checks, real SMTP, and user acceptance are tracked separately. C02/C03 and foundation entries below remain historical.

## Sprint 2 — WO dan Mekanik

**ID:** Jalankan Herd dan PostgreSQL untuk membuka aplikasi. Build frontend yang sudah tersedia tidak memerlukan `npm run dev`. Setelah menerima versi baru di laptop lain, gunakan perintah berikut dari folder project di PowerShell. Tidak perlu `migrate:fresh`, seed ulang data aplikasi, atau membuat akun mekanik melalui pgAdmin.

**EN:** Run Herd and PostgreSQL to open the app. An existing production frontend build does not require `npm run dev`. On another laptop after receiving this release, use these commands from the project directory in PowerShell. Do not reset migrations or reseed application data; mechanic accounts are created through the UI.

```powershell
git switch main
git pull --ff-only origin main
git fetch origin --tags
git show --no-patch --oneline sprint-2-work-orders-v1
composer install
npm.cmd ci
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

### Automated local verification / Verifikasi otomatis lokal

- [x] Dua migration Sprint 2 Ran; 12 migration total di database aplikasi. Jumlah record lama sebelum/sesudah upgrade sama: users 4, workshops 2, memberships 2, plans 3, subscriptions 2, customers 0, vehicles 0. / Two Sprint 2 migrations ran, 12 total; existing record counts were unchanged.
- [x] Constraint tenant, nomor unik, satu kendaraan aktif, lifecycle, event append-only, serta rollback/upgrade diverifikasi pada PostgreSQL testing. / Tenant constraints, numbering, active-vehicle uniqueness, lifecycle, append-only events, and rollback/upgrade verified on test PostgreSQL.
- [x] Suite penuh akhir **335 tes / 1.813 assertion** lolos, termasuk regresi foundation/C01–C03, akses role, token setup, SMTP gagal/kirim ulang, log-mailer guard, CSRF, form stale, snapshot, dan concurrency dengan proses PHP/koneksi PostgreSQL terpisah. / Final full suite passed: **335 tests / 1,813 assertions**, including regressions, roles, setup tokens, SMTP failure/retry, log-mailer guard, CSRF, stale forms, snapshots, and independent-process PostgreSQL concurrency.
- [x] **3 tes JavaScript**, Pint seluruh project, Composer validation strict, kompilasi Blade, dan build Vite lolos. / **3 JavaScript tests**, project-wide Pint, strict Composer validation, Blade compilation, and Vite build passed.
- [x] Browser Edge otomatis desktop **1280×900** dan HP **390×844** pada server terpisah `127.0.0.1:8011`: owner Draft/edit/terima/penugasan/batal, mekanik mulai/selesai, kasir buat/tugaskan/mulai/selesai, pembuatan sandi mekanik, tambah akun/kirim ulang/aktivasi, pagination, IDOR, versi stale, pergantian bengkel, dan menu HP lolos. Tidak ada error JavaScript atau overflow horizontal. / Automated Edge desktop/mobile flows passed for all three roles, onboarding, activation, pagination, IDOR, concurrency conflicts, workshop switches, and mobile navigation; no JavaScript errors or horizontal overflow.
- [x] Herd menyajikan BengkelOS melalui Nginx dengan HTTP 200 pada `http://bengkel-saas.test`. / Herd serves the actual BengkelOS page through Nginx with HTTP 200.

**ID:** Angka di atas hanya bukti pemeriksaan 10 November, bukan data contoh yang ditambahkan ke akun aplikasi. Database otomatis bernama `bengkel_saas_testing`; data aplikasi tidak di-reset. Rincian implementasi ada di [Sprint 2](SPRINT_2_WORK_ORDERS.md).

**EN:** These counts record November 10 verification and are not sample data added to application accounts. Automation uses `bengkel_saas_testing`; application data was not reset. See [Sprint 2](SPRINT_2_WORK_ORDERS.md) for implementation details.

### User acceptance and real SMTP / Penerimaan user dan SMTP asli

**ID:** Semua kotak di bagian ini sengaja kosong sampai lu mencoba dan mengonfirmasi hasilnya. Gunakan data uji yang mudah dikenali dan email yang lu kuasai. Untuk perbandingan role kasir, gunakan akun uji kasir yang sudah tersedia; Sprint 2 hanya menyediakan pembuatan akun mekanik. Tes role kasir otomatis memakai akun sintetis di database testing.

**EN:** These boxes remain empty until the user performs and confirms the checks. Use recognizable test records and email accounts you control. Cashier acceptance requires an existing test cashier; Sprint 2 only adds mechanic account creation. Automated cashier checks use synthetic users in the test database.

- [ ] Owner: buka **Mekanik → Tambah mekanik**, isi nama dan email yang belum terdaftar. Akun tampil **Menunggu pembuatan sandi**; email masuk inbox asli. / Create a new mechanic; verify pending status and real-inbox delivery.
- [ ] Buka tautan email, buat sandi, lalu login sebagai mekanik. Link lama tidak dapat dipakai ulang. Sebelum sandi dibuat, akun tidak bisa login atau ditugaskan. / Complete setup, log in, and confirm single use and pending-account restrictions.
- [ ] Coba email yang sudah terdaftar; akun tidak ditautkan atau diduplikasi. Kirim ulang tautan dibatasi sementara. / Existing email is rejected without linking; resend is throttled.
- [ ] Owner/kasir: cari kendaraan, buka detail lalu **Buat WO**. Pelanggan awal mengikuti kendaraan; bisa diganti atau dilepas sebelum diterima. / Start from a vehicle and check default/changeable/optional customer.
- [ ] **Simpan draft**, ubah kendaraan/pelanggan dan keluhan/catatan, lalu **Terima pekerjaan**. Setelah Antre, kendaraan/pelanggan tidak bisa diganti; catatan masih bisa diperbaiki. / Save/edit a draft, receive it, and verify the edit boundary.
- [ ] Coba buat WO kedua untuk kendaraan yang masih aktif; ditolak. Detail kendaraan menyediakan **Buka WO aktif**. / Duplicate active work is blocked and vehicle details link to it.
- [ ] Mulai WO tanpa mekanik; ditolak dengan pesan jelas. Tugaskan mekanik aktif yang sudah membuat sandi; **Mulai pekerjaan** berhasil. / Starting requires a ready, active, assigned mechanic.
- [ ] Mekanik: daftar hanya menampilkan tugas sendiri; bisa mulai/menyelesaikan, tanpa tombol edit detail, penugasan, batal, atau menu pengelolaan Mekanik. / Mechanics see/process only their assignments and have no management actions.
- [ ] Owner/kasir: ganti mekanik saat Dikerjakan; mekanik lama kehilangan akses WO tersebut dan mekanik baru dapat melanjutkan. Tidak dapat melepas mekanik tanpa pengganti saat Dikerjakan. / Reassign in-progress work and verify access switches.
- [ ] Owner: penonaktifan mekanik dengan WO aktif ditolak. Setelah dialihkan/dibatalkan, nonaktifkan; akses bengkel ini terputus, riwayat tetap ada. Aktifkan lagi untuk memulihkan akses. / Deactivation requires resolving active assignments and preserves history.
- [ ] Selesaikan WO. Semua tindakan ubah/penugasan/batal hilang; pekerjaan lanjutan memakai WO baru. Selesai tidak menampilkan status lunas. / Completed work is final; follow-up uses a new WO and implies no payment.
- [ ] Owner: batalkan Draft/Antre/Dikerjakan dengan alasan. Alasan wajib dan hasil final. Kasir/mekanik tidak boleh membatalkan. / Verify owner-only cancellation and mandatory reason.
- [ ] Ubah master kendaraan/pelanggan/mekanik sesudah penerimaan/penugasan; snapshot penerimaan dan riwayat penugasan lama tetap benar. / Master edits do not rewrite receipt/assignment snapshots.
- [ ] Buka WO dalam dua tab, simpan perubahan di satu tab, lalu kirim form lama: konflik jelas tanpa menimpa. Ganti bengkel di tab lain: form sebelumnya ditolak dan menyediakan tautan muat ulang. / Verify stale-version and switched-workshop conflicts.
- [ ] Daftar: filter aktif/semua/status final, pencarian nomor/pelat, pagination daftar serta riwayat berjalan. Tanggal tampil sesuai timezone bengkel. / Check filters, search, pagination, and workshop-timezone dates.
- [ ] HP/desktop: form, tombol, menu, pencarian pelanggan, dan riwayat nyaman dipakai tanpa melebar. / Check real-device usability and horizontal overflow.
- [ ] SMTP asli: coba tautan kedaluwarsa (>60 menit), kirim ulang, serta reset sandi akun lama. Jangan kirim sandi, token, atau isi `.env` ke chat/Git. / Verify expiry, resend, and existing-account resets with real SMTP.

**ID:** Selesai paket ini, berhenti sebelum Sprint 3. Tidak ada jasa, spare part, stok, invoice, pembayaran, komisi, atau laporan operasional dalam rilis ini.

**EN:** Stop after this package, before Sprint 3. This release does not include services, parts, stock, invoices, payments, commissions, or operational reports.

**ID — Catatan historis C02/C03:** Kendaraan dan alur pencarian pelat selesai diimplementasikan pada 10 November. [Checklist C02/C03](#c02c03-laptop-follow-up--pemeriksaan-laptop-c02c03) mencatat persiapan lokal, 10 migration, suite penuh, dan browser otomatis versi tersebut. Centang historis dipertahankan; tes user dan SMTP nyata tidak dianggap otomatis lolos.

**EN — Historical C02/C03 record:** Vehicles and plate lookup were implemented on November 10. The [C02/C03 checklist](#c02c03-laptop-follow-up--pemeriksaan-laptop-c02c03) records local preparation, 10 migrations, the full suite, and browser checks for that version. Historical checks are preserved; user and real SMTP checks are not inferred to pass.

**ID — Verifikasi lokal C01, 10 November:** Codex memeriksa `3fa9bfb` beserta perbaikan koneksi UTC dan tes constraint PostgreSQL 18. Dependency, 9 migration Ran, build, 221 tes / 1.073 assertion, Pint, validasi/audit Composer, kompilasi Blade, dan browser Edge otomatis desktop/HP lolos. Detail ada di [bagian C01](#c01-laptop-follow-up--pemeriksaan-laptop-c01). Catatan lama di bawah tetap historis. Browser otomatis memakai server terpisah `127.0.0.1:8011` dan database `bengkel_saas_testing`, bukan data akun aplikasi. SMTP nyata tidak diuji ulang.

**EN — C01 local verification, November 10:** Codex checked `3fa9bfb` plus the UTC connection and PostgreSQL 18 constraint-test fixes. Dependencies, 9 migrations, build, 221 tests / 1,073 assertions, Pint, Composer validation/audit, Blade compilation, and automated desktop/mobile Edge checks passed. See the [C01 section](#c01-laptop-follow-up--pemeriksaan-laptop-c01). Earlier records remain historical. Browser tests used a separate `127.0.0.1:8011` server and `bengkel_saas_testing`, not application account data. Real SMTP was not retested.
>
> F08 cloud review / Review cloud F08: 2026-11-09 — see / lihat [FOUNDATION_REVIEW.md](FOUNDATION_REVIEW.md). Earlier laptop results below are unchanged. / Hasil laptop terdahulu di bawah dipertahankan.
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

**ID — F08:** Mulai pembaruan versi terbaru dari [checklist F08](#f08-laptop-follow-up--pemeriksaan-laptop-f08). Centang dan tabel hasil versi sebelumnya tetap catatan historis; laporan cloud F08 tidak mengubah hasil laptop/SMTP Anda. Tag target kode: `f08-foundation-hardening-v1`.

**EN — F08:** Start the latest update with the [F08 checklist](#f08-laptop-follow-up--pemeriksaan-laptop-f08). Existing checkmarks and result tables remain historical records; F08 cloud checks do not change your laptop/SMTP results. Target code tag: `f08-foundation-hardening-v1`.

**ID — C01:** Untuk versi pelanggan terbaru, mulai dari [checklist C01](#c01-laptop-follow-up--pemeriksaan-laptop-c01): pull, migration baru, build, dan pengujian pelanggan per role/tenant. Baseline cloud C01: **221 tes / 1.069 assertion**; ini bukan hasil laptop. Target kode: `c01-customer-management-v1`. Semua centang dan laporan versi sebelumnya tetap dipertahankan.

**EN — C01:** For the customer release, start with the [C01 checklist](#c01-laptop-follow-up--pemeriksaan-laptop-c01): pull, the new migration, build, and customer role/tenant checks. C01 cloud baseline: **221 tests / 1,069 assertions**, not laptop results. Target code: `c01-customer-management-v1`. All previous-version checkmarks and reports are preserved.

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
| F07 — Application UI Shell | Sudah di-merge; tag `f07-application-shell-v1` / Merged; tagged |
| F08 — Foundation Test & Hardening | Validasi kode cloud lolos; lihat laporan Foundation / Cloud code validation passes; see Foundation review |
| C01 — Customer Management | Implementasi dan tes cloud lolos; pengujian laptop belum dikonfirmasi / Implementation and cloud tests pass; laptop checks pending |
| C02–C03 — Vehicle + UX | Belum diimplementasikan / Not implemented |

**ID:** F02 menyediakan registrasi akun, login/ingat saya, logout, lupa/reset kata sandi, dan halaman akun `/dashboard`. Pada F05 registrasi membuat workshop baru, membership owner, dan trial gratis 14 hari; akun lama tidak otomatis diubah. F08 menutup validasi kode Phase 1 — Foundation pada scope yang tersedia. Hasil laptop dicatat terpisah; checklist ini bukan izin otomatis melanjutkan roadmap.

**EN:** F02 provides account registration, login/remember-me, logout, forgot/reset password, and a protected `/dashboard` account page. F05 registration creates a new workshop, owner membership, and free 14-day trial; existing accounts are not automatically changed. F08 completes Phase 1 Foundation code validation for the implemented scope. Laptop results remain separate; this checklist does not authorize automatic roadmap implementation.

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

### F08 laptop follow-up / Pemeriksaan laptop F08

**ID:** F08 memperbarui empat dependency Symfony dan seeder default, serta menambah pengaman database tes. Tidak ada migration baru. Simpan perubahan lokal sebelum beralih branch. Jangan reset database aplikasi, menimpa `.env`, atau mengganti `APP_KEY` yang sudah dipakai. Docker hanya runtime verifikasi cloud, tidak wajib di laptop.

**EN:** F08 patches four Symfony dependencies and the default seeder, and adds a testing-database guard. No migrations are added. Preserve local work before switching branches. Keep application data, `.env`, and the existing `APP_KEY`. Docker was only the cloud verification runtime and is not required locally.

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
composer install
php artisan config:clear
php artisan migrate:status
npm.cmd run build
```

- [ ] Pembaruan selesai, 8 migration tetap `Ran`, halaman login dan aset tampil normal. Jalankan `php artisan migrate` hanya jika masih ada migration lama yang pending. / Update succeeds, all eight migrations remain applied, and login/assets render normally; apply outstanding earlier migrations if needed.
- [ ] Registrasi akun uji baru menghasilkan bengkel, role Pemilik, dan trial 14 hari; logout lalu login kembali berhasil. Akun tanpa membership mengikuti halaman pemilihan yang aman. / New test registration provisions owner/workshop/trial; logout/login and zero-membership behavior work.
- [ ] Jalankan pemeriksaan UI F07 dan isolasi A/B F06 yang belum Anda coba: bengkel, role, paket, serta pemilihan harus mengikuti membership masing-masing. / Complete outstanding F07 UI and F06 A/B isolation checks against actual memberships.
- [ ] Jika pernah memakai seeder versi lama, tinjau akun demo yang dibuatnya. F08 mencegah akun demo baru, tetapi tidak menghapus atau mengganti password akun lama. Jangan menghapus akun hanya karena emailnya cocok; pastikan asalnya dahulu. / Review legacy seeded accounts if applicable; F08 does not alter existing accounts, and an email alone is not proof an account is disposable.
- [ ] Konfirmasi hasil email nyata di bagian 5/7 dengan tanggal dan versi yang diuji. Catatan lama memiliki centang serta status tertunda yang perlu diselaraskan oleh penguji berdasarkan hasil nyata, bukan tes cloud. / Confirm real-email results with a date/version; reconcile earlier checked items and pending reports using actual evidence.
- [ ] Setelah reset password dari browser kedua, browser pertama harus diminta login lagi pada request terlindungi berikutnya. Opsional: uji juga login yang redirect dashboard-nya ditahan hingga reset selesai. / A password reset invalidates the other browser on its next protected request, including a newly logged-in session whose first dashboard request was delayed.
- [ ] Opsional: ikuti bagian 6 untuk database sementara `bengkel_saas_testing` dan `DB_URL` kosong, pastikan host/role khusus tes, lalu `php artisan test`. Baseline F08: **161 tes / 703 assertion**. Guard menolak konfigurasi lain sebelum `RefreshDatabase`; jangan melepas guard agar tes dapat berjalan pada database aplikasi. / Run the optional suite only on the verified disposable testing database; fix rejected configuration instead of bypassing the guard.
- [ ] Catat hasil F08 di tabel berikut tanpa mengubah hasil terdahulu hanya berdasarkan laporan cloud. / Record F08 results separately from previous-version evidence.

| Pemeriksaan F08 / F08 check | Hasil laptop / Laptop result |
| --- | --- |
| Tanggal + commit/tag / Date + commit/tag | Belum diisi / Pending |
| Composer, migration status, build | Belum diuji / Not tested |
| Registrasi, login/logout, trial / Registration, login/logout, trial | Belum diuji / Not tested |
| UI HP/desktop + isolasi A/B / Mobile/desktop + A/B isolation | Belum diuji / Not tested |
| Review akun seeder lama (jika ada) / Legacy seeder account review (if applicable) | Belum dikonfirmasi / Not confirmed |
| Email nyata + pencabutan session / Real email + session invalidation | Belum dikonfirmasi untuk F08 / Not confirmed for F08 |
| Suite lokal (opsional) / Local suite (optional) | Belum dijalankan / Not run |

### C01 laptop follow-up / Pemeriksaan laptop C01

**ID — Hasil Codex 2026-11-10:** Centang berlabel Codex berasal dari terminal lokal atau browser otomatis pada database testing terpisah. Item manual lain tetap kosong sampai seluruh kondisinya dicoba. Blok persiapan di bawah tidak perlu diulang untuk mulai mencoba menu Pelanggan di Herd.

**EN — Codex results 2026-11-10:** Codex-labeled checks come from local commands or automated browser testing on a separate database. Other manual items stay unchecked until every condition is verified. Repeating the preparation block below is unnecessary to start trying Customers through Herd.

- [x] Codex: `composer install`, `npm.cmd ci`, dan `npm.cmd run build` selesai sesuai lock file. / Locked dependency installation and frontend build completed.
- [x] Codex: konfigurasi dibersihkan; 9 migration Ran. Jumlah data user/bengkel/membership/paket/subscription lama sebelum dan sesudah upgrade sama. / Configuration/migrations passed; existing application row counts were preserved.
- [x] Codex: koneksi PostgreSQL aplikasi UTC; halaman login BengkelOS melalui Herd/Nginx HTTP 200. / The application connection uses UTC; Herd serves the correct login page with HTTP 200.
- [x] Codex: suite penuh PostgreSQL lokal 18 lolos **221 tes / 1.073 assertion**; Pint, validasi/audit Composer, dan kompilasi Blade lolos. / The local full suite and code/dependency/view checks passed.
- [x] Codex: Edge otomatis 1280×900 dan 390×844 lolos untuk tambah nama saja/field lengkap, telepon huruf ditolak dengan nama dipertahankan, edit/kosongkan field opsional, nama sama, pencarian nama case-insensitive/literal `%_`, pagination 20+1, nama panjang tanpa overflow, dan navigasi HP. / Automated desktop/mobile customer, validation, search, pagination, layout, and navigation checks passed.
- [x] Codex: browser otomatis memverifikasi kasir tambah/edit, mekanik tanpa tombol tulis dan form tulis 403, detail lintas tenant A/B 404 dua arah, serta form edit lama 404 sesudah ganti bengkel. Tidak ada error JavaScript. / Browser checks verified cashier writes, mechanic restrictions, bidirectional tenant denial, stale-form rejection, and no JavaScript errors.

**ID:** Pemeriksaan manual lengkap di bawah tetap terpisah. Batas panjang field, penolakan DELETE, pencabutan role, dan edge case lain tercakup suite PHP; bukan berarti seluruhnya sudah dicoba user lewat browser.

**EN:** The complete manual checklist below remains separate. Field limits, DELETE rejection, role revocation, and other edge cases are covered by PHP tests, not all manually exercised by the user.

**ID:** C01 menambahkan tabel pelanggan, policy, form tambah/edit, detail, dan pencarian. Keputusan user: tanpa hapus. Gunakan data lokal khusus pengujian; jangan menjalankan `migrate:fresh`, `migrate:reset`, atau rollback pada data aplikasi untuk pemeriksaan ini. Tidak ada dependency baru. Simpan perubahan lokal sebelum beralih branch, pertahankan `.env` dan `APP_KEY` yang sudah dipakai.

**EN:** C01 adds the customer table, policy, create/edit forms, detail, and search. The user chose no deletion. Use local test data; do not run fresh/reset migrations or rollback against application data for this checklist. No dependencies are added. Preserve local work before switching branches and retain `.env` and the existing `APP_KEY`.

```powershell
git status --short --branch
git switch main
git pull --ff-only origin main
git fetch origin --tags
composer install
php artisan config:clear
php artisan migrate
php artisan migrate:status
npm.cmd run build
```

- [x] Codex: migration `2026_11_09_000000_create_customers_table` berhasil; total 9 migration Ran dan jumlah data akun/bengkel/trial lama terjaga. / Nine migrations are applied and existing application row counts are preserved.
- [ ] User: login dengan akun aplikasi lama dan pastikan dashboard normal setelah upgrade C01. / Log in with an existing application account and verify the dashboard after upgrading.
- [ ] Owner dengan bengkel aktif melihat menu **Pelanggan** di sidebar desktop dan navigasi HP. Daftar awal kosong menampilkan pesan yang sesuai, bukan data buatan. / Owners see customer navigation on desktop/mobile; empty lists show the real empty state.
- [ ] Tambah pelanggan dengan nama saja berhasil. Coba juga nama, telepon `081234567890` atau `+62 812-3456`, dan catatan multiline; detail sesuai input dan nol awal telepon dipertahankan. / Name-only and full customer forms save correctly, including leading phone zeros and multiline notes.
- [ ] Nama wajib dan maksimal 255 karakter. Telepon opsional, maksimal 32 karakter, hanya angka/tanda baca telepon yang dijelaskan; huruf ditolak dengan pesan Indonesia dan isian lain tetap ada. Catatan opsional maksimal 5.000 karakter. / Validate required/length-limited names, optional phone/notes, Indonesian errors, and preserved input.
- [ ] Ubah nama/telepon/catatan berhasil. Kosongkan telepon/catatan lalu simpan: detail menunjukkan **Belum diisi/Belum ada catatan**. Tidak tercipta pelanggan duplikat karena update. / Edit and clearing optional fields work without creating another record.
- [ ] Nama dan nomor telepon yang sama boleh dipakai oleh pelanggan berbeda. Data hanya masuk ke bengkel aktif. / Duplicate names/phones are allowed and records belong to the active workshop.
- [ ] Cari sebagian nama tanpa membedakan huruf besar/kecil atau sebagian telepon. Hasil benar, pencarian kosong kembali ke daftar, dan kata yang tidak cocok menampilkan pesan tidak ditemukan. `%`, `_`, dan backslash diperlakukan sebagai karakter literal. / Verify partial case-insensitive name/phone search, empty/no-result states, and literal wildcard characters.
- [ ] Jika ada lebih dari 20 fixture pelanggan, pagination tidak mengulang/melewatkan baris dan mempertahankan pencarian. Jika fixture belum tersedia, biarkan item ini kosong; suite cloud sudah memeriksa kasusnya. / With over 20 test records, pagination is stable and retains the search; leave unchecked if fixtures are unavailable.
- [ ] Pada lebar sekitar 390 px dan desktop, daftar/form/detail tetap terbaca, nama panjang tidak membuat halaman melebar, tombol/menu bekerja, dan menu **Pelanggan** menandai halaman aktif. / Check readable responsive layouts, long names, working controls, and active navigation.
- [ ] Jika punya fixture role sah: kasir dapat tambah/edit; mekanik dapat lihat/cari tetapi tidak melihat tombol tambah/edit. Mekanik yang membuka `/customers/create` atau `/customers/{id}/edit` untuk record bengkelnya mendapat 403. / Authorized role fixtures confirm cashier writes and mechanic reads, with direct mechanic write-form access denied.
- [ ] Dengan dua akun fixture A/B di browser terpisah, daftar/pencarian A tidak menampilkan B dan sebaliknya. Buka URL detail pelanggan B memakai akun A: 404, tanpa detail B. / Separate A/B sessions cannot see each other's records, even through a copied detail URL.
- [ ] Jika user fixture punya dua bengkel: buka edit pelanggan A, ganti ke bengkel B pada tab lain, lalu kirim form lama. Request ditolak (404); pelanggan A tetap utuh. Buat form baru setelah kembali ke bengkel yang benar. / An old edit form cannot update a customer after the active workshop changes.
- [ ] Tidak ada tombol hapus untuk owner/kasir/mekanik. Tes otomatis memverifikasi request DELETE ditolak dan record tetap ada. / No role gets deletion UI; automated tests verify DELETE is unavailable and records survive.
- [x] Codex: suite penuh termasuk seluruh tes Customer dijalankan pada database lokal khusus testing, **221 tes / 1.073 assertion** lolos. Baseline cloud awal 221 tes / 1.069 assertion; tambahan assertion memastikan data induk/membership tetap ada setelah penolakan hapus. / The full local suite passed; extra assertions check parent/membership preservation after restricted deletion.
- [ ] Catat hasil aktual di tabel C01 berikut, tanpa mencentang berdasarkan hasil cloud. / Record actual C01 results below without inferring them from cloud checks.

**ID:** C01 belum menyediakan UI pengelolaan membership/role. Untuk pemeriksaan kasir/mekanik, dua tenant, atau pencabutan membership, gunakan fixture uji sah bila tersedia; jangan menaikkan privilege akun asli hanya untuk tes. Jika tidak ada fixture, tandai belum diuji dan gunakan bukti suite otomatis sebagai catatan terpisah.

**EN:** C01 does not include membership/role management UI. Use authorized test fixtures for cashier/mechanic, multiple tenants, or revocation checks; do not elevate real accounts merely for testing. If fixtures are unavailable, leave the manual checks pending and keep automated evidence separate.

| Pemeriksaan C01 / C01 check | Hasil laptop / Laptop result |
| --- | --- |
| Tanggal + commit/tag / Date + commit/tag | 2026-11-10 — Codex; `3fa9bfb` + perbaikan UTC/tes PG18 lokal / local UTC/PG18 test fixes |
| Migration, data lama, build / Migration, existing data, build | PASS — 9 Ran; jumlah data lama terjaga; Composer/npm install, build, dan Herd login HTTP 200 / Existing row counts preserved |
| Tambah/detail/edit + validasi / Create/detail/edit + validation | PASS — browser otomatis terpisah: nama saja/field lengkap, telepon huruf ditolak, isian dipertahankan, edit/kosongkan opsional; batas lain di suite PHP / Isolated automated browser flows; remaining boundaries covered by PHP |
| Pencarian + pagination / Search + pagination | PASS — browser otomatis: nama case-insensitive, `%_` literal, 20+1 baris; edge case lain di suite PHP / Automated browser checks; other edge cases covered by PHP |
| Role owner/kasir/mekanik / Owner/cashier/mechanic | PASS — browser otomatis: owner/kasir tambah-edit; mekanik lihat, form tulis 403 / Automated browser role checks |
| Isolasi A/B + pergantian bengkel / A/B isolation + switching | PASS — browser otomatis: detail A/B 404 dua arah dan form edit lama 404 sesudah ganti bengkel / Bidirectional tenant denial and stale-form rejection |
| UI HP/desktop + tanpa hapus / Mobile/desktop + no deletion | PASS — Edge otomatis 1280×900 dan 390×844, tanpa overflow/error JS; DELETE ditolak di suite PHP / Automated Edge layouts and PHP deletion denial |
| Suite lokal (opsional) / Local suite (optional) | PASS — PHP 8.4.26 + PostgreSQL 18.6, 221 tes / 1.073 assertion; Pint, validasi/audit Composer, kompilasi Blade lolos / All checks passed |
| Cek user pada akun aplikasi / User checks on application accounts | Tetap kosong sampai dicoba; hasil otomatis menggunakan data testing terpisah / Remain unchecked until tried on application accounts |
| SMTP nyata / Real SMTP | Tidak diuji ulang; hasil historis user tetap dipertahankan / Not retested; historical user results preserved |

### C02/C03 laptop follow-up / Pemeriksaan laptop C02/C03

**ID:** User mengonfirmasi aturan kendaraan sama dengan pelanggan: owner/kasir tambah-edit, mekanik lihat/cari, tanpa hapus. Kedua langkah diminta sekaligus. Tidak perlu mengulang persiapan yang dicentang untuk mulai tes melalui Herd. Gunakan data uji saat mencoba; jangan reset atau rollback database aplikasi. Tag rilis gabungan: `c02-vehicle-management-v1` dan `c03-customer-vehicle-ux-v1`.

**EN:** The user confirmed the customer access rules for vehicles: owner/cashier writes, mechanic reads/searches, no deletion. Both steps were requested together. Checked preparation need not be repeated to start testing through Herd. Use test data and never reset/roll back the application database. Combined release tags: `c02-vehicle-management-v1` and `c03-customer-vehicle-ux-v1`.

- [x] Codex, 2026-11-10: migration `2026_11_10_000000_create_vehicles_table` diterapkan; **10 migration Ran**. Jumlah akun/bengkel/membership/paket/subscription/pelanggan lama terjaga. / Migration applied and existing application row counts preserved.
- [x] Codex: **296 tes / 1.434 assertion** pada database PostgreSQL testing terpisah lolos; termasuk regresi Foundation/C01, isolasi tenant, role, CSRF, input tidak valid, duplicate/race, constraint dan rollback/reapply. / Full PHP suite passed on the separate testing database.
- [x] Codex: **3 tes JavaScript** untuk respons pencarian lama, perubahan tenant, dan kegagalan pencarian dengan pilihan terjaga lolos. / Customer-picker JavaScript regression tests passed.
- [x] Codex: Pint, validasi Composer, kompilasi Blade, serta `npm.cmd run build` lolos. / Formatting, Composer validation, Blade compilation, and frontend build passed.
- [x] Codex: Edge otomatis 1280×900 dan 390×844 lolos untuk pelat ditemukan/belum ditemukan, tambah dengan pelat terisi, kendaraan tanpa pelanggan, cari/pilih/lepas pelanggan, duplikat dengan isian terjaga, edit/kosongkan opsional, tautan pelanggan, pagination, serta menu HP. Tidak ada error JavaScript atau halaman melebar. / Automated desktop/mobile browser flows passed without JS errors or horizontal overflow.
- [x] Codex: browser otomatis membuktikan kasir tambah-edit, mekanik tanpa akses tulis/pemilih pelanggan, ID kendaraan tenant lain 404 dua arah, serta form edit lama ditolak sesudah pindah bengkel. Fixture hanya di `bengkel_saas_testing`. / Automated browser checks verified role restrictions, bidirectional tenant denial, and stale-form rejection on disposable fixtures only.

**ID — Yang masih Anda coba sendiri:** Kotak di bawah sengaja kosong. Hasil browser otomatis adalah pemeriksaan Codex pada data terpisah, bukan laporan bahwa user sudah mencoba dengan akun aplikasi sendiri.

**EN — Your own manual checks:** Boxes below intentionally remain unchecked. Codex automated browser results on isolated data do not imply the user tried them with application accounts.

- [ ] Login lewat `http://bengkel-saas.test`; buka menu **Kendaraan**. Pastikan bengkel aktif benar dan tampilan nyaman di perangkat Anda. / Verify the active workshop and layout with your application account/device.
- [ ] Dari ringkasan, cari pelat baru seperti **B 1234 UJI**. Klik **Tambah kendaraan dengan pelat ini**, simpan tanpa pelanggan, lalu cari lagi dengan **b1234uji**. Harus membuka kendaraan yang sama. / Test missing → prefilled create → normalized repeat lookup.
- [ ] Ubah kendaraan, pilih **Cari atau pilih pelanggan**, cari nama/telepon pelanggan bengkel Anda, pilih hasilnya, isi merek/model/tahun/catatan, lalu simpan. / Search and select an optional customer, add details, and save.
- [ ] Buka pelanggan dari detail kendaraan. Daftar kendaraannya harus memuat pelat tadi; **Tambah kendaraan pelanggan** membuka form dengan pelanggan terpilih. / Verify both customer/vehicle links and preselection.
- [ ] Coba pelat sama dengan spasi/huruf berbeda: ditolak pada bengkel yang sama. Pelat yang sama boleh dibuat pada bengkel uji lain. / Verify tenant-local normalized uniqueness.
- [ ] Coba pelat kosong/tanda baca/karakter tak didukung, tahun di luar 1900–tahun depan, serta merek/model/catatan terlalu panjang. Pesan Indonesia tampil dan isian lain terjaga. / Check validation and input preservation.
- [ ] Lepas pelanggan dan kosongkan field opsional: tersimpan sebagai kosong; detail menampilkan **Tanpa pelanggan / Belum diisi**. / Verify optional-field clearing and customer detachment.
- [ ] Jika punya fixture role sah, kasir boleh tambah-edit; mekanik lihat/cari saja, URL form tulis langsung 403. Tidak ada tombol hapus untuk semua role. / Check authorized role fixtures and no deletion.
- [ ] Jika punya dua akun/bengkel uji, hasil daftar/pencarian/pilihan pelanggan tidak bocor A/B. Form kendaraan lama setelah pindah bengkel harus ditolak. / Verify A/B isolation and stale forms with safe fixtures.
- [ ] Opsional pada database testing terpisah yang diverifikasi: `php artisan test --filter=Vehicle`, `php artisan test`, `node --test tests/Frontend/customer-picker.test.js`. / Run optional regressions only against the verified disposable database.

| Pemeriksaan C02/C03 / C02/C03 check | Hasil / Result |
| --- | --- |
| Tanggal + pemeriksa / Date + tester | 2026-11-10 — Codex; browser Edge otomatis pada fixture terpisah / Automated Edge on isolated fixtures |
| Migration / upgrade | PASS — 10 Ran, jumlah data lama terjaga / Existing row counts preserved |
| PHP / JavaScript | PASS — 296 tes / 1.434 assertion; 3 tes JavaScript |
| Build / lint / view | PASS — frontend, Pint, Composer, Blade |
| Browser desktop/HP / Desktop/mobile | PASS — create/edit/lookup/picker, role, tenant, pagination, input, layout |
| Tes user pada akun aplikasi / User application-account tests | Belum dikonfirmasi / Not confirmed |
| SMTP nyata / Real SMTP | Tidak diuji ulang; laporan historis dipertahankan / Not retested; earlier reports preserved |

**ID:** Migration baru menambah tabel kendaraan dan kunci unik pelanggan pendukung untuk FK gabungan. Rollback menghapus data kendaraan, sehingga tes rollback hanya dijalankan pada database sementara. Tidak ada Work Order, riwayat servis, inventory, invoice, atau pembayaran yang ditambahkan. C02/C03 menyelesaikan implementasi Sprint 1; modul berikutnya menunggu permintaan terpisah.

**EN:** The new migration adds vehicles and a supporting customer unique key for the composite FK. Rollback deletes vehicle records and was tested only on the disposable database. No Work Orders, service history, inventory, invoices, or payments were added. C02/C03 completes Sprint 1 implementation; further modules require a separate request.

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

**ID:** Foundation dan implementasi Sprint 1 C01–C03 selesai. Berikutnya **W01 — Work Order Foundation** hanya jika diminta secara eksplisit. Lengkapi hasil manual user/SMTP sesuai checklist saat ada waktu. Tag milestone menyimpan versi kode; bukan backup database atau `.env`.

**EN:** Foundation and Sprint 1 C01–C03 implementation are complete. **W01 — Work Order Foundation** requires an explicit request. Complete user manual/SMTP records when time permits. Milestone tags preserve code, not databases or `.env` files.
