# Sprint 5 — Dashboard dan Laporan / Dashboard and Reports

## Scope and access / Cakupan dan akses

**ID:** D01–D02 memakai ledger Sprint 2–4 tanpa tabel transaksi baru atau perubahan catatan lama. Owner melihat angka keuangan, laporan, CSV, dan cetak A4/PDF browser. Kasir mendapat ringkasan operasional dan jumlah invoice belum lunas, serta akses invoice sebelumnya. Mekanik hanya mendapat hitungan dan riwayat WO yang ditugaskan kepadanya. Pembatasan query berlaku sebelum agregasi/pagination. Tidak ada bypass `super_admin`.

**EN:** D01–D02 read Sprint 2–4 records without new transaction tables or rewriting history. Financial aggregates/reports and every report CSV/print endpoint are owner-only. Cashiers retain invoice access and receive operational counts. Mechanics receive only their assigned work. Queries are scoped before aggregation/pagination; platform administration does not bypass membership.

## Definitions / Definisi angka

| Angka / Metric | Definisi / Definition |
| --- | --- |
| WO aktif / Active work | Draft, Antre/open, Dikerjakan/in_progress; mechanic queries also require their assignment. |
| Selesai hari ini / Completed today | `completed_at` within the workshop's local day. Completion does not imply payment / Selesai belum berarti lunas. |
| Pembayaran / Recorded payments | `payments.amount` on `paid_at`, including entries reversed later / Termasuk catatan yang dikoreksi kemudian. |
| Koreksi / Recording corrections | Original payment amount/method on `payment_reversals.created_at`; not a refund / Bukan uang keluar atau refund. |
| Penerimaan tercatat bersih / Net recorded receipts | Payments minus corrections in the selected period; can be negative / Dapat negatif. |
| Nilai invoice / Invoice value | Snapshot invoice items/total at `issued_at`; subtract at `voided_at` only if previously issued. Drafts and voided drafts are excluded / Draft tidak dihitung. |
| Jasa/spare part / Service/part values | Snapshot `invoice_items.item_type` subtotals, never current catalog/WO prices. Installments are not allocated across item types / Pembayaran sebagian tidak dibagi secara perkiraan. |
| Stok menipis / Low stock | Active items with a configured minimum and `current_stock <= minimum_stock`. |
| Piutang / Receivables | Positive effective invoice remainder at the chosen cutoff; zero invoices are not outstanding / Invoice nol bukan piutang. |

**ID:** Retur barang hanya mengubah stok. Laporan keuangan tetap mengikuti invoice resmi. Invoice pengganti mempunyai nomor baru; invoice lama tetap dalam riwayat terbit/batal. Angka ini bukan laba, HPP, atau akuntansi.

**EN:** Stock returns do not recalculate official invoices or financial reports. Replacement invoices retain a separate number and the prior issue/void trail. These figures do not represent profit, cost of goods, or accounting.

## Historical receivables / Piutang historis

**ID:** `/reports/receivables` default Sekarang, dengan pilihan Akhir hari atau waktu lokal sampai detik. Waktu masa depan ditolak. Untuk `T`, invoice harus sudah terbit (`issued_at <= T`). Pembatalan hanya berlaku ketika `voided_at <= T`. Pembayaran dihitung ketika `paid_at <= T`, kecuali sudah ada reversal dengan `created_at <= T`. Sisa efektif menjadi nol setelah pembatalan. Status/saldo saat ini tidak menentukan posisi masa lalu. Umur menggunakan selisih tanggal lokal sejak penerbitan.

**EN:** The default is Now; end-of-day and exact local-second cutoffs are supported and future cutoffs rejected. Include invoices issued by `T`, void only when the void timestamp has been reached, and count payments recorded by `T` unless already reversed by `T`. Voided invoices have zero effective debt. Current status/balance must never determine historical position. Age is the local calendar-day difference from issuance.

| Kejadian / Event | Posisi / Position |
| --- | --- |
| Issue 500,000 at 09:00 | 09:30: 500,000 |
| Payment 200,000 at 10:00 | 10:30: 300,000 |
| Reverse payment at 11:00 | 11:30: 500,000 |
| Void at 12:00 | 12:30: zero effective debt / nol piutang efektif |

**ID:** `/reports/receivables/{invoice}` menampilkan posisi dan kronologi sampai cutoff yang sama. Invoice yang belum terbit pada waktu itu tidak menampilkan snapshot/kejadian masa depan. Ini penelusuran pencatatan server, bukan rekonstruksi perpindahan uang di luar aplikasi atau perubahan draft. CSV/cetak/pagination mempertahankan cutoff, timezone, konteks bengkel, dan filter melalui URL.

**EN:** Invoice tracing shows position and chronology through the same cutoff, with no future events or pre-issue snapshot disclosure. It reconstructs server records, not unrecorded money movements or draft edits. Export, printing, and pagination preserve the cutoff, timezone, tenant context, and filters in their URLs.

## Pages, periods, and history / Halaman, periode, dan riwayat

- `/dashboard`: reload on opening or **Perbarui**, no polling/caching/sample data. Account/trial information remains below operations / Informasi akun/trial tetap di bawah.
- `/reports/payments`: daily/method summary and paginated payment/correction records / Ringkasan harian/metode dan rincian berhalaman.
- `/reports/invoice-values`: snapshot issue/void values split by service/part / Nilai terbit/batal snapshot jasa/spare part.
- `/reports/receivables`: historical debt list and invoice tracing / Daftar piutang dan penelusuran per waktu.
- `/service-history`: default completed/cancelled; filters by stable vehicle ID, WO customer ID, mechanic ID, plate/WO/customer text, status, and creation/completion dates. Vehicle/customer detail links allow all time; historical WO detail remains the source for service/usage/return snapshots / Default Selesai/Dibatalkan; detail WO mempertahankan rincian historis.
- Existing `/invoices?payment=outstanding`: includes unpaid and partially paid official invoices, excluding zero/paid/draft/void / Mencakup belum bayar dan dibayar sebagian.

**ID:** Laporan periode default bulan berjalan sampai hari ini. Preset hari ini/tujuh hari/bulan/rentang khusus maksimum 366 hari. Awal hari lokal dikonversi ke UTC; rentang bersifat `[awal, awal hari setelah akhir)`. Piutang memakai cutoff tersendiri dan menjangkau seluruh umur invoice, tanpa pembatasan ke bulan berjalan. Riwayat mengikuti pelanggan pada WO, bukan pemilik kendaraan saat ini; perubahan pelat tidak memutus `vehicle_id`. Draft belum memiliki snapshot penerimaan dan ditandai jelas.

**EN:** Period reports default to month-to-date, with today/seven-day/month/custom presets and a maximum inclusive 366 days. Local day bounds convert to half-open UTC intervals. Receivables use an independent cutoff across all available invoice history. Service history uses the WO customer and stable vehicle identity rather than current vehicle ownership/plate. Drafts have no reception snapshot and are labeled accordingly.

## Read integrity, export, and indexes / Integritas baca, ekspor, dan indeks

**ID:** Respons memakai transaksi PostgreSQL `REPEATABLE READ READ ONLY`, tanpa lock transaksi operasional. Query web sudah dimaterialisasi sebelum snapshot selesai; CSV diproses bertahap 200 record selama snapshot yang sama. Suite `RefreshDatabase` memakai transaksi fixture yang sudah terbuka; tes proses PHP terpisah memeriksa isolation/read-only dan perubahan bersamaan yang benar-benar committed.

**EN:** Responses use PostgreSQL `REPEATABLE READ READ ONLY`, without locking operational writes. Web data is materialized within the snapshot and CSV iterates batches of 200 within it. Fixture tests reuse their enclosing transaction; a separate PHP process verifies real isolation/read-only behavior while committed payment/reversal writes proceed.

**ID:** Agregat uang dihitung dengan PostgreSQL numeric dan dikembalikan sebagai string desimal tepat, termasuk jumlah di atas `PHP_INT_MAX`. CSV UTF-8 BOM mengutip teks berawalan formula; kolom angka server tetap desimal tepat. Tidak ada batas jumlah baris ekspor. Semua output menampilkan periode/cutoff, timezone, serta waktu dibuat. Cetak A4/PDF memakai browser dan merangkum seluruh hasil, tetapi rincian hanya halaman aktif dengan keterangan eksplisit. Printer fisik dan penerimaan user tetap pengujian terpisah.

**EN:** SQL numeric sums return exact decimal strings even above `PHP_INT_MAX`. UTF-8 BOM CSV protects untrusted formula-like text while retaining exact server numeric fields, and exports have no silent row cap. All outputs identify period/cutoff/timezone/generation time. Browser A4/PDF printing includes full summaries and explicitly labeled current-page details. Physical printing and user acceptance remain separate checks.

**ID / EN:** Migration `2026_11_11_010000_add_reporting_indexes.php` adds only five indexes / hanya menambah lima indeks:

| Index | Columns / Kolom | Predicate / Predikat |
| --- | --- | --- |
| `payments_report_time` | workshop_id, paid_at, id | — |
| `reversals_report_time` | workshop_id, created_at, id | — |
| `invoices_report_issued` | workshop_id, issued_at, id | issued_at IS NOT NULL |
| `invoices_report_voided` | workshop_id, voided_at, id | issued_at IS NOT NULL AND voided_at IS NOT NULL |
| `work_orders_vehicle_history` | workshop_id, vehicle_id, id | — |

**ID:** Indeks lama tetap dipakai untuk piutang/membership/pelanggan. Tes EXPLAIN memeriksa kelayakan indeks berurutan dengan sequential scan/sort dinonaktifkan pada transaksi tes; ini bukan benchmark data production. Planner tetap bebas memilih sequential scan pada tabel kecil. Migration turun-naik diuji pada database disposable dan tidak mengubah record.

**EN:** Existing indexes continue supporting outstanding balances/membership/customer history. EXPLAIN tests establish ordered-index eligibility with sequential scans/sorts disabled only inside the test transaction; they are not production benchmarks. Tiny tables may naturally favour sequential scans. The migration round trip is verified on a disposable database without record changes.

## Release boundary / Batas rilis

**ID — Hasil nyata 2026-11-11:** Suite penuh **419 tes / 2.732 assertion**, termasuk 37 kasus tambahan Sprint 5, lolos pada PostgreSQL lokal khusus testing. Tiga tes JavaScript, Pint seluruh project, Composer strict, kompilasi Blade, dan build Vite lolos. Browser Edge desktop 1280×900 dan HP 390×844 lolos untuk ketiga role, CSV, cutoff historis, serta A4/PDF, tanpa page error/overflow. Ekspor 205 WO melewati batch 200; query history tetap bounded. Satu migration indeks diterapkan, total 15 Ran, seluruh jumlah record aplikasi lama tetap sama. Herd/Nginx mengembalikan BengkelOS HTTP 200. Review kode/keamanan dilakukan lokal; ini bukan persetujuan reviewer eksternal atau penerimaan user.

**EN — Verified 2026-11-11:** The full suite passed **419 tests / 2,732 assertions**, including 37 additional Sprint 5 cases, on the dedicated local PostgreSQL test database. Three JavaScript tests, project-wide Pint, strict Composer validation, Blade compilation, and Vite build passed. All-role Edge desktop/mobile checks covered CSV, historical cutoffs, and A4/PDF without page errors/overflow. Streaming crossed a 200-record batch with 205 WOs; history query counts remained bounded. All 15 application migrations ran with prior row counts retained, and Herd/Nginx served the app successfully. Review was local code/security review, not external reviewer approval or human acceptance.

**ID:** Branch `feat/sprint-5-dashboard-reports`; target tag `sprint-5-dashboard-reports-v1`. Hasil otomasi dan penerimaan manual dicatat terpisah di [checklist](MANUAL_TESTING.md). Setelah paket ini selesai, berhenti sebelum validasi bengkel nyata dan perbaikan operasional milestone Alpha. Manual Sprint 2–5 belum dikonfirmasi.

**EN:** Work is released from the dedicated branch with the requested tag. Automation and human acceptance are tracked separately in the checklist. Stop after this package; real-workshop validation and operational fixes are the next Alpha milestone. Sprint 2–5 manual acceptance remains unconfirmed.
