# Sprint 3 — Services, Parts, Inventory / Jasa, Spare Part, Stok

> Approved package / Paket disetujui: I01–I05. Date / Tanggal: 2026-11-10.
> Release tag / Tag rilis: `sprint-3-services-inventory-v1`.

## Outcome / Hasil

**ID:** Katalog jasa dan spare part → item pada WO → konfirmasi pemakaian → stok berkurang → retur fisik bila barang kembali. Sprint 3 berhenti sebelum invoice, pembayaran, refund uang, harga beli/HPP, supplier, purchase order, diskon/pajak, dan laporan operasional. Subtotal baris belum merupakan tagihan.

**EN:** Service/part catalogs → WO lines → confirm consumption → stock decreases → record physical returns. Sprint 3 stops before invoices, payments, money refunds, purchase costs/COGS, suppliers, purchase orders, discounts/taxes, and operational reports. Line subtotals are not bills.

## Access / Akses

| Action / Aksi | Owner | Cashier / Kasir | Mechanic / Mekanik |
| --- | --- | --- | --- |
| View catalogs and stock ledger / Lihat katalog dan ledger stok | Yes / Ya | Yes / Ya | No / Tidak |
| Manage catalog and activation / Kelola katalog dan keaktifan | Yes / Ya | No / Tidak | No / Tidak |
| Manage active WO lines and prices / Kelola item dan harga WO aktif | Yes / Ya | Yes / Ya | No / Tidak |
| Restock, usage, active WO return / Restock, pemakaian, retur WO aktif | Yes / Ya | Yes / Ya | No / Tidak |
| Stock adjustments and final WO returns / Koreksi stok dan retur WO final | Yes / Ya | No / Tidak | No / Tidak |
| View WO lines and selling prices / Lihat item WO dan harga jual | All active-workshop WOs / Semua WO bengkel aktif | Same / Sama | Own assignments / Tugas sendiri |

**ID:** Membership aktif, role, dan tenant diverifikasi di server setiap operasi, termasuk di dalam transaksi. Tidak ada bypass super admin. Seluruh form menyertakan konteks bengkel; mutasi memakai versi katalog/stok atau WO sesuai target. Konflik stale/pergantian bengkel menghasilkan HTTP 409 dengan tautan muat ulang.

**EN:** Active membership, role, and tenancy are checked server-side on each operation, including within transactions. No platform-admin bypass exists. Forms include workshop context; mutations check catalog/stock or WO versions as appropriate. Stale versions/workshop switches return HTTP 409 with reload guidance.

## Catalogs / Katalog

**ID:** Owner membuat jasa (nama, deskripsi opsional, harga standar) dan spare part (nama, SKU opsional, satuan, harga jual, batas minimum opsional). SKU trim/huruf besar, unik per bengkel; kosong menjadi NULL. Satuan default pcs, tetap setelah dibuat, tanpa konversi. Semua jumlah bilangan bulat; harga Rupiah nonnegatif. Barang mulai dari stok nol; Restock mencatat stok awal. Tidak ada edit saldo dalam katalog atau hard delete. Nonaktif memblokir pemilihan baru, restock, dan pemakaian; retur/koreksi/riwayat tetap tersedia.

**EN:** Owners create services (name, optional description, default price) and parts (name, optional SKU, unit, selling price, optional minimum stock). SKUs are trimmed/uppercased and tenant-unique; blank becomes NULL. Units default to pcs and are immutable, without conversions. Quantities are integers; Rupiah prices are nonnegative. Parts start at zero stock; restock records opening balances. Catalog edits cannot set balances; hard deletion is unavailable. Inactive items cannot be newly selected, restocked, or consumed; returns/adjustments/history remain available.

## WO lines / Baris WO

**ID:** Jasa/spare part wajib katalog. Baris menyimpan snapshot nama, deskripsi jasa, SKU/satuan barang, jumlah, harga, serta subtotal hasil server. Perubahan master tidak menulis ulang snapshot. Owner/kasir dapat menyesuaikan harga WO tanpa mengubah katalog. Jasa aktif dapat diedit/dibatalkan; baris batal dipertahankan dalam riwayat.

**EN:** Lines require catalog references and freeze names, service descriptions, part SKUs/units, quantities, prices, and server-computed subtotals. Master changes do not rewrite snapshots. Owners/cashiers can override WO prices independently. Active service lines can be edited/cancelled; cancelled lines remain historical records.

**ID:** Barang Rencana tidak mengurangi atau mencadangkan stok. Konfirmasi hanya saat Dikerjakan dan memakai seluruh jumlah satu baris; revisi jumlah sebelum konfirmasi jika perlu. Setelah Dipakai, identitas/jumlah dikunci, harga boleh diubah selama WO aktif dengan event audit tanpa movement tambahan. Jumlah/subtotal menggunakan pemeriksaan overflow dan format Rupiah tanpa konversi float.

**EN:** Planned parts neither consume nor reserve stock. Confirmation is limited to work in progress and consumes an entire line; adjust planned quantities first when needed. After usage, identity/quantity lock while active-WO prices remain editable with audit events and no stock movement. Subtotals use overflow checks and Rupiah formatting avoids floating-point conversion.

**ID:** Penyelesaian ditolak bila masih ada Rencana; owner/kasir harus memakai atau membatalkannya dahulu. WO lama/tanpa item tetap bisa selesai. Pembatalan WO otomatis membatalkan rencana tersisa dalam transaksi yang sama, tanpa mengembalikan pemakaian. Selesai/Dibatalkan mengunci item WO.

**EN:** Completion is blocked by unresolved plans; owners/cashiers must consume or cancel them first. Legacy/empty WOs can still complete. WO cancellation atomically cancels remaining plans without restoring consumed stock. Completed/cancelled WOs lock their lines.

## Inventory and returns / Stok dan retur

**ID:** Jenis ledger: restock, work_order_usage, work_order_return, adjustment_in, adjustment_out. Jumlah positif, jenis menentukan arah. Koreksi dan retur wajib alasan. Catat pelaku, UTC, saldo sebelum/sesudah, snapshot identitas barang, dan sumber pemakaian. Movement append-only dilindungi trigger PostgreSQL dan tidak mempunyai endpoint edit/hapus. Koreksi memakai movement baru.

**EN:** Ledger types are restock, work_order_usage, work_order_return, adjustment_in, and adjustment_out. Positive quantities use the type for direction. Adjustments/returns require reasons. Records include actor, UTC time, before/after balances, part snapshots, and usage references. PostgreSQL protects append-only movements; no edit/delete endpoints exist. Corrections require new movements.

**ID:** Retur sebagian/berulang dibatasi sisa pemakaian asal. Hanya barang yang kembali secara fisik dan layak disimpan yang diretur. Retur mengubah stok saja: jumlah asli, harga, dan subtotal baris tidak otomatis berkurang. Tampilan menunjukkan jumlah dipakai/diretur terpisah; aturan nilai tagihan/refund ditentukan Sprint 4.

**EN:** Partial/repeated returns cannot exceed remaining original consumption. Only physically returned, usable goods should re-enter stock. Returns affect inventory only: original quantities, prices, and subtotals remain intact. Usage and return quantities display separately; billing/refund rules belong to Sprint 4.

**ID:** Retur WO aktif menambah event/versi WO. Sesudah final, owner mencatat retur dari riwayat stok dengan referensi pemakaian asal; status, versi, item, harga/subtotal, dan event WO final tidak berubah. Riwayat stok serta jumlah retur yang ditampilkan dapat bertambah. Retur barang nonaktif tetap diizinkan.

**EN:** Active-WO returns append events/increment WO versions. After finalization, owners return through stock history against original consumption; final WO status, version, lines, prices/subtotals, and events remain unchanged. Inventory history and displayed returned quantities can still increase. Inactive parts remain returnable.

## Persistence and interfaces / Penyimpanan dan antarmuka

**ID:** Satu migration baru menambah services, spare_parts, work_order_services, work_order_spare_parts, dan inventory_movements. PK BIGINT; FK gabungan melindungi referensi tenant dan hubungan movement ke barang/baris/WO/sumber yang sama. Constraint menjaga SKU, jumlah, nilai, saldo, jenis, dan satu usage per baris. Stok dan ledger berubah atomik dengan lock workshop → WO bila ada → barang → baris/sumber terkait. Semua write dalam bengkel yang sama mengikuti lock workshop terlebih dahulu.

**EN:** One new migration adds services, spare_parts, work_order_services, work_order_spare_parts, and inventory_movements. BIGINT keys and composite FKs protect tenancy and movement part/line/WO/source relationships. Constraints enforce SKUs, quantities, prices, balances, types, and one usage per line. Balance/ledger changes commit together under workshop → optional WO → part → related line/source locks. Every write within a workshop locks the workshop first.

**ID:** Transaksi stok membawa operation_key unik per bengkel dan fingerprint pelaku, target, payload, serta versi. Replay yang sama mengembalikan hasil lama tanpa movement/event baru; payload/pelaku berbeda ditolak. Otorisasi tetap diperiksa ulang sebelum replay. Foreign/stale ID tidak membuktikan izin.

**EN:** Stock writes carry tenant-unique operation keys and fingerprints binding actor, targets, payload, and versions. Matching replays return the prior result without new movements/events; mismatched actors/payloads fail. Authorization is rechecked before replay. Foreign/stale IDs never prove access.

**ID:** Resource /services dan /spare-parts menyediakan daftar/detail/form berhalaman. Endpoint terpisah menangani activation, movements, final returns, serta baris services/parts di bawah /work-orders/{work_order}. Tindakan use/cancel/returns terpisah dari update umum. Semua memakai autentikasi, session, konteks bengkel, Form Requests, Policies, CSRF, dan UI Indonesia. Tidak ada dependency baru.

**EN:** /services and /spare-parts expose paginated catalogs/details/forms. Dedicated endpoints handle activation, movements, final returns, and nested WO service/part lines. Use/cancel/returns are separate from ordinary updates. All use authentication, session checks, workshop context, Form Requests, Policies, CSRF, and Indonesian UI. No dependencies were added.

## Verification / Verifikasi

**ID — Hasil lokal 10 November:** Suite penuh **358 tes / 2.151 assertion**, **3 tes JavaScript**, Pint seluruh project, Composer validation strict, kompilasi Blade, dan build Vite lolos. Browser Edge desktop 1280×900 dan HP 390×844 lolos untuk owner/kasir/mekanik, termasuk alur stok sampai retur final dan form stale. Tidak ada error JavaScript/overflow horizontal. Tes concurrency memakai proses PHP dan koneksi PostgreSQL terpisah. Migration baru Ran (13 total); jumlah record aplikasi lama tetap sama sebelum/sesudah upgrade.

**EN — November 10 local results:** The full suite passed **358 tests / 2,151 assertions**, plus **3 JavaScript tests**, project-wide Pint, strict Composer validation, Blade compilation, and Vite build. Edge desktop/mobile flows passed for all roles, including final returns and stale forms, with no JavaScript errors/horizontal overflow. Concurrency used separate PHP processes/PostgreSQL connections. One new migration ran (13 total), preserving existing application record counts.

**ID:** Hasil otomatis dan penerimaan user dicatat terpisah di [checklist Sprint 3](MANUAL_TESTING.md#sprint-3--jasa-spare-part-dan-stok). Testing manual Sprint 2 tetap belum dikonfirmasi. Jangan menjalankan migrate:fresh atau rollback pada database aplikasi.

**EN:** Automated results and user acceptance are tracked separately in the [Sprint 3 checklist](MANUAL_TESTING.md#sprint-3--jasa-spare-part-dan-stok). Sprint 2 manual acceptance remains unconfirmed. Never reset/roll back application data.
