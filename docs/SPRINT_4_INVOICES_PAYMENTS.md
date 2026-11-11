# Sprint 4 — Invoices and Payments / Invoice dan Pembayaran

> Approved package / Paket disetujui: B01–B03. Date / Tanggal: 2026-11-11.
> Release tag / Tag rilis: `sprint-4-invoices-payments-v1`.

## Outcome / Hasil

**ID:** WO Selesai → periksa draft → terbitkan → bayar bertahap → lunas → cetak. Draft dapat disiapkan sejak Antre/Dikerjakan. Pembayaran tetap terpisah dari status WO. Tidak ada DP, saldo pelanggan, payment gateway, refund uang, diskon/pajak terpisah, item bebas, akuntansi, atau laporan Sprint 5. Manual acceptance Sprint 2/3/4 belum dikonfirmasi.

**EN:** Completed WO → review draft → issue → installments → paid → print. Drafts can start in queue/in progress. Payments are separate from WO state. Advances, customer credit, gateways, money refunds, separate taxes/discounts, arbitrary lines, accounting, and Sprint 5 reports are outside this package. Sprint 2/3/4 manual acceptance remains unconfirmed.

## Permissions / Izin

| Action / Aksi | Owner | Cashier / Kasir | Mechanic / Mekanik |
| --- | --- | --- | --- |
| View/create drafts, refresh, notes, price adjustment / Lihat/buat draft, perbarui, catatan, harga | Yes / Ya | Yes / Ya | No / Tidak |
| Issue completed-WO invoices / Terbitkan untuk WO Selesai | Yes / Ya | Yes / Ya | No / Tidak |
| Record payments and print / Catat pembayaran dan cetak | Yes / Ya | Yes / Ya | No / Tidak |
| Void unpaid invoices / Batalkan invoice tanpa pembayaran valid | Yes / Ya | No / Tidak | No / Tidak |
| Reverse incorrect payment records / Batalkan salah catat pembayaran | Yes / Ya | No / Tidak | No / Tidak |

**ID:** Membership aktif/role/tenant diperiksa ulang pada server dan dalam transaksi; super_admin tidak mendapat bypass. Mekanik tetap dapat melihat harga item WO tugas sendiri sesuai Sprint 3, tetapi tidak dapat membuka invoice, pembayaran, atau halaman cetak. Semua mutasi memakai konteks bengkel, versi, operation key, Form Requests, Policies, dan CSRF.

**EN:** Active membership, role, and tenancy are rechecked server-side within transactions; super_admin has no bypass. Mechanics retain assigned-WO item-price visibility from Sprint 3, without invoice/payment/print access. Mutations use workshop context, versions, operation keys, Form Requests, Policies, and CSRF.

## Drafts and source snapshots / Draft dan snapshot sumber

**ID:** Satu invoice Draft/Terbit per WO, ditegakkan partial unique. Draft berasal dari jasa aktif dan spare part Dipakai dikurangi seluruh retur tercatat saat penyalinan. Rencana/baris batal/jumlah bersih nol tidak ditagihkan. Kuantitas/jenis bersumber dari WO, tidak bisa diedit langsung. Snapshot menyimpan deskripsi, satuan, jumlah, harga, subtotal; customer/pelat mengikuti snapshot penerimaan WO, bukan master terbaru. Tanpa nama pelanggan ditampilkan Pelanggan umum.

**EN:** A partial unique index allows one draft/issued invoice per WO. Drafts copy active services and net consumed parts after recorded returns. Plans, cancelled lines, and zero net quantities are excluded. Types/quantities cannot be manually changed. Snapshots preserve descriptions, units, quantities, prices, and subtotals; customer/plate data uses WO receipt snapshots rather than current masters, with a general-customer fallback.

**ID:** Owner/kasir dapat menyesuaikan harga satuan dengan alasan wajib dan catatan draft tanpa mengubah WO final. Perbarui dari WO secara eksplisit mengganti seluruh rincian dan menghapus penyesuaian harga, dengan konfirmasi dan event before/after. Catatan invoice dipertahankan. Fingerprint mencakup versi/status WO, identitas, item sumber, dan agregasi retur, termasuk retur final yang tidak menaikkan versi WO. Sumber stale memblokir penerbitan sampai refresh. Tidak ada sinkronisasi diam-diam.

**EN:** Owners/cashiers can override unit prices with mandatory reasons and edit draft notes without changing final WOs. Explicit refresh rebuilds all lines and clears overrides after confirmation, recording before/after events while preserving invoice notes. Fingerprints cover WO version/state, identities, source lines, and returns, including post-final returns that do not increment WO versions. Stale sources block issuance until refreshed; no silent synchronization occurs.

**ID:** Jumlah/harga Rupiah integer, subtotal dan penjumlahan total diperiksa terhadap overflow tanpa float. Invoice dibatasi 1.000 baris sumber untuk membatasi pemakaian memori; kelebihan ditolak, tidak dipotong otomatis.

**EN:** Integer quantities/Rupiah calculations use checked multiplication/addition and no floats. Source lines are capped at 1,000 to bound memory; excess is rejected, never silently truncated.

## Issue, void, replacement / Terbit, batal, pengganti

**ID:** Terbit hanya setelah WO Selesai dan sumber masih sesuai. Draft tanpa baris ditolak; baris sah dengan total nol boleh terbit dan langsung Lunas—tanpa pembayaran, tanpa record fiktif. Nomor INV-000001 dialokasikan saat terbit dari counter workshop yang dikunci, tanpa reset tanggal/pemakaian ulang. Draft hanya memiliki label Draft #id. Snapshot bengkel/kontak dibekukan bersama rincian komersial. Setelah terbit, item/harga/total/catatan/identitas tidak dapat diubah.

**EN:** Issuance requires a completed WO and matching source. Empty invoices are rejected; valid zero-total invoices are paid without fabricated payment records. INV-000001 numbers are allocated only upon issuance using a locked workshop counter, without date resets/reuse. Drafts use internal labels. Workshop/contact and commercial snapshots freeze together; issued items, prices, totals, notes, and identities are immutable.

**ID:** Owner dapat membatalkan dengan alasan jika tidak ada pembayaran valid. Koreksi invoice terbit memakai void → draft pengganti → nomor baru, dengan referensi invoice sebelumnya. Record/nomor lama tetap ada. WO Dibatalkan tidak dapat ditagihkan; pembatalan WO otomatis membatalkan draft invoice dalam transaksi WO yang sama. Invoice void tidak mengubah WO/stok. Retur setelah terbit hanya stok dan tidak mengubah tagihan; refund uang belum tersedia.

**EN:** Owners may void with a reason when no valid payments remain. Material corrections use void → replacement draft → new number, linking predecessors and retaining history. Cancelled WOs cannot be billed; WO cancellation atomically voids its draft. Voiding an invoice does not change WO/stock. Post-issue returns remain stock-only and do not rewrite bills or refund money.

## Payments and printing / Pembayaran dan cetak

**ID:** Pembayaran hanya invoice Terbit, nominal positif maksimal sisa, metode cash/bank_transfer/other; Lainnya wajib keterangan. Referensi/catatan opsional lainnya. paid_at adalah waktu server UTC, tanpa backdate/DP/kembalian. Pembayaran parsial/campuran diperbolehkan. Status Belum dibayar/Dibayar sebagian/Lunas dihitung dari total pembayaran tanpa reversal, tidak disimpan terpisah.

**EN:** Only issued invoices accept positive payments up to the remaining balance. Methods are cash/bank_transfer/other; other requires a note. References/other notes are optional. paid_at is server UTC, without backdating/advances/change calculation. Installments/mixed methods are supported. Unpaid/partial/paid states derive from valid non-reversed payments rather than redundant stored balances.

**ID:** Salah input dikoreksi owner dengan reversal seluruh catatan, alasan wajib, tanpa mengubah/menghapus pembayaran asal. Pembayaran benar dicatat baru sesudahnya. Reversal tidak boleh dua kali dan bukan refund uang. Invoice dengan pembayaran valid tidak dapat di-void. Riwayat payment/reversal/event append-only dilindungi PostgreSQL.

**EN:** Owners correct input mistakes by fully reversing the record with a reason, preserving the original and entering corrected payments separately. Reversal is single-use and not a money refund. Valid payments block invoice voiding. PostgreSQL protects append-only payments/reversals/events.

**ID:** Halaman /invoices menyediakan daftar/filter dokumen/pembayaran, pencarian nomor/WO/pelat/pelanggan, detail dan riwayat berhalaman. Halaman cetak A4 tetap login/tenant-authorized dan no-store; Draft/Void diberi penanda. Tombol cetak memakai browser, termasuk simpan PDF tanpa generator server. Cetak merangkum seluruh pembayaran valid dan menampilkan maksimal 100 catatan pembayaran terbaru dengan jumlah total serta rujukan riwayat lengkap di aplikasi.

**EN:** /invoices provides document/payment filters, number/WO/plate/customer searches, details, and paginated history. Authenticated tenant-authorized A4 printing uses no-store headers and Draft/Void marks. Browser printing/PDF needs no server generator. Print summaries include all valid payments and explicitly show up to the latest 100 records with total counts and full-history guidance.

## Persistence and transactions / Penyimpanan dan transaksi

**ID:** Satu migration baru menambah invoices, invoice_items, payments, payment_reversals, invoice_events, serta workshop.next_invoice_number. PK BIGINT, FK gabungan mengikat tenant/WO/item/payment/reversal/pengganti yang benar. Constraint memeriksa tipe, nilai, alasan, uniqueness dan lifecycle. Trigger melindungi snapshot final serta history, menolak pembayaran berlebih, dan melarang terbit dari WO belum Selesai atau total tidak cocok dengan item.

**EN:** One migration adds five billing tables and a workshop invoice counter. BIGINT keys/composite FKs protect tenant/WO/line/payment/reversal/predecessor integrity. Constraints enforce values, types, reasons, uniqueness, and lifecycle. Triggers protect final snapshots/history, prevent overpayment, and reject issuance from unfinished WOs or inconsistent item totals.

**ID:** Lock selalu workshop → WO → invoice → record terkait, kompatibel dengan inventory. Sumber retur diperiksa ulang di bawah lock saat issue. Semua write memakai versi dan operation_key unik per tenant; fingerprint mengikat pelaku, aksi, target, dan payload. Replay berotorisasi ulang mengembalikan hasil lama tanpa nomor/payment/event ganda. Payload berbeda ditolak. Semua data lama dipertahankan; rollback hanya database testing. Tidak ada dependency baru atau endpoint DELETE.

**EN:** Locks follow workshop → WO → invoice → related records, consistent with inventory. Issuance rechecks returns under lock. Versions and tenant-unique operation keys bind actors/actions/targets/payloads. Reauthorized matching replays return prior results without duplicate numbers/payments/events; mismatches fail. Upgrades preserve old data; rollback is test-only. No dependencies or DELETE endpoints were added.

## Verification / Verifikasi

**ID — Hasil lokal 11 November:** Suite penuh **382 tes / 2.467 assertion**, tiga tes JavaScript, Pint, Composer validation strict, kompilasi Blade, dan build Vite lolos. Browser Edge desktop 1280×900 dan HP 390×844 lolos untuk owner/kasir/mekanik, termasuk net quantity, stale source, penerbitan, pembayaran campuran sampai lunas, reversal, void/reissue, retur sesudah terbit, tenant/form conflict, cetak A4, dan file PDF browser. Tanpa error JavaScript/overflow horizontal. Concurrency memakai proses PHP/koneksi PostgreSQL terpisah. Satu migration baru Ran (14 total), jumlah data lama tetap sama.

**EN — November 11 results:** The full suite passed **382 tests / 2,467 assertions**, plus three JavaScript tests, Pint, strict Composer validation, Blade compilation, and Vite build. Desktop/mobile Edge passed all-role billing, correction/replacement, post-issue returns, security/conflicts, A4, and generated-PDF checks without JS errors/overflow. Concurrency used independent PHP processes/PostgreSQL connections. One migration ran (14 total), preserving pre-existing row counts.

**ID:** Hasil otomatis dan penerimaan user dicatat terpisah di [checklist Sprint 4](MANUAL_TESTING.md#sprint-4--invoice-dan-pembayaran). Jangan menandai tes manual Sprint 2/3/4 lolos dari hasil sintetis. Stop sebelum Sprint 5.

**EN:** Automation and user acceptance are tracked separately in the [Sprint 4 checklist](MANUAL_TESTING.md#sprint-4--invoice-dan-pembayaran). Synthetic checks do not confirm Sprint 2/3/4 manual acceptance. Stop before Sprint 5.
