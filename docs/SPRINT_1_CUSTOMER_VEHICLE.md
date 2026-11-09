# Sprint 1 — Customer + Vehicle / Pelanggan + Kendaraan

> Recorded / Dicatat: 2026-11-09
>
> Status: Planning only; implementation has not started / Rencana saja; implementasi belum dimulai.

**EN:** This document expands [ROADMAP.md, Sprint 1](ROADMAP.md#15-sprint-1--customer--vehicle). Foundation F01–F08 code validation is complete at tag `f08-foundation-hardening-v1`; the [Foundation review](FOUNDATION_REVIEW.md) and existing [manual results](MANUAL_TESTING.md) remain separate. This request authorizes documentation only. Start a C step only when explicitly requested, then stop after that step.

**ID:** Dokumen ini merinci [ROADMAP.md, Sprint 1](ROADMAP.md#15-sprint-1--customer--vehicle). Validasi kode Foundation F01–F08 selesai pada tag `f08-foundation-hardening-v1`; [review Foundation](FOUNDATION_REVIEW.md) dan [hasil manual](MANUAL_TESTING.md) tetap dicatat terpisah. Permintaan ini hanya mengizinkan dokumentasi. Mulai langkah C hanya saat diminta secara eksplisit, lalu berhenti setelah langkah tersebut selesai.

## Step order and progress / Urutan langkah dan progres

| Step / Langkah | Outcome / Hasil yang dituju | Current status / Status saat ini |
| --- | --- | --- |
| C01 — Customer Management | Add, view, edit, and search workshop customers / Tambah, lihat, edit, dan cari pelanggan bengkel | Planned; settle access/deletion rules before coding / Direncanakan; tetapkan aturan akses/penghapusan sebelum coding |
| C02 — Vehicle Management | Record vehicles with an optional customer / Catat kendaraan dengan pelanggan opsional | Planned after C01 / Direncanakan setelah C01 |
| C03 — Customer + Vehicle UX | Search plate → open existing vehicle or register a new one / Cari pelat → buka kendaraan yang ada atau daftarkan baru | Planned after C02 / Direncanakan setelah C02 |

## C01 — Customer Management / Manajemen Pelanggan

**EN:** Start with the customer module only. Use the existing active-workshop context, Blade/Tailwind shell, Laravel validation and server-side authorization. Conceptual fields follow [DATABASE.md, Customers](DATABASE.md#14-customers): ID, workshop, name, optional phone/notes, and timestamps. Duplicate names are allowed; phone is not assumed globally unique.

**ID:** Mulai dari modul pelanggan saja. Gunakan konteks bengkel aktif, shell Blade/Tailwind, validasi Laravel, dan otorisasi server yang tersedia. Field konseptual mengikuti [DATABASE.md, Customers](DATABASE.md#14-customers): ID, bengkel, nama, telepon/catatan opsional, serta timestamps. Nama boleh sama; telepon tidak dianggap unik secara global.

Implementation steps / Langkah implementasi:

- [ ] Settle the access matrix and deletion behavior below; record the decision before coding. / Tetapkan matriks akses dan perilaku penghapusan di bawah; catat keputusan sebelum coding.
- [ ] Add the customer migration/model and test factory using existing database conventions; preserve shared migrations. / Tambahkan migration/model pelanggan dan factory tes sesuai convention database; pertahankan migration yang sudah dibagikan.
- [ ] Implement a paginated list, detail, create/edit forms, and search within the verified active workshop. / Implementasikan daftar berhalaman, detail, form tambah/edit, dan pencarian pada bengkel aktif terverifikasi.
- [ ] Enforce the chosen role rules on every endpoint; derive `workshop_id` server-side and scope all record lookups. / Terapkan aturan role yang dipilih pada setiap endpoint; tentukan `workshop_id` di server dan batasi seluruh pencarian record sesuai tenant.
- [ ] Test validation, allowed/denied roles, guest access, revoked membership, forged IDs/tenant fields, A/B isolation, duplicate names, and escaped output. / Uji validasi, role diizinkan/ditolak, tamu, membership dicabut, manipulasi ID/field tenant, isolasi A/B, nama sama, dan escaping output.
- [ ] Check customer flows on desktop/mobile, migration behavior, the relevant/full suite, and frontend build; record actual results. / Periksa alur pelanggan pada desktop/HP, perilaku migration, suite relevan/penuh, dan build frontend; catat hasil nyata.

### Proposed C01 access / Usulan akses C01

**EN:** The following is the initial proposal from the discussion, not a permission policy already implemented. Confirm it when C01 implementation is requested. Vehicle-specific permissions are to be settled for C02.

**ID:** Berikut usulan awal dari diskusi, bukan policy izin yang sudah diimplementasikan. Konfirmasikan ketika implementasi C01 diminta. Izin khusus kendaraan ditetapkan untuk C02.

| Workshop role / Role bengkel | View/search customers / Lihat/cari pelanggan | Add/edit customers / Tambah/edit pelanggan | Delete / Hapus |
| --- | --- | --- | --- |
| `owner` | Proposed: allowed / Usulan: boleh | Proposed: allowed / Usulan: boleh | Undecided / Belum ditentukan |
| `cashier` | Proposed: allowed / Usulan: boleh | Proposed: allowed / Usulan: boleh | Undecided / Belum ditentukan |
| `mechanic` | Proposed: read only / Usulan: hanya baca | Proposed: denied / Usulan: ditolak | Undecided / Belum ditentukan |

**EN:** All proposed access is limited to the workshop where membership is currently valid. Platform `super_admin` does not gain a tenant-membership bypass. Decide whether C01 includes deletion, prevents deletion when referenced, or uses another approved approach. Preserve future vehicle/service history; do not automatically add soft deletion or cascading record removal. The roadmap's CRUD tests must reflect the behavior actually chosen.

**ID:** Semua usulan akses terbatas pada bengkel dengan membership yang masih valid. `super_admin` platform tidak memperoleh bypass membership tenant. Tentukan apakah C01 mencakup penghapusan, menolak hapus ketika masih direferensikan, atau memakai pendekatan lain yang disepakati. Pertahankan riwayat kendaraan/servis berikutnya; jangan otomatis menambah soft delete atau penghapusan record berantai. Tes CRUD pada roadmap harus mengikuti perilaku yang benar-benar dipilih.

## C02 — Vehicle Management / Manajemen Kendaraan

**EN:** Implement only after C01 is ready and C02 is explicitly requested. Follow [DATABASE.md, Vehicles](DATABASE.md#15-vehicles): workshop, optional customer, plate and normalized plate, optional brand/model/year/notes, and timestamps. A vehicle may exist without a customer; do not create a fake generic customer to satisfy the relation.

**ID:** Implementasikan setelah C01 siap dan C02 diminta secara eksplisit. Ikuti [DATABASE.md, Vehicles](DATABASE.md#15-vehicles): bengkel, pelanggan opsional, pelat dan pelat normalisasi, merek/model/tahun/catatan opsional, serta timestamps. Kendaraan boleh tanpa pelanggan; jangan membuat pelanggan umum palsu untuk memenuhi relasi.

- [ ] Finalize vehicle permissions, validation, deterministic plate normalization, and deletion/reference behavior within the approved design. / Tetapkan izin kendaraan, validasi, normalisasi pelat deterministik, serta perilaku hapus/referensi sesuai desain yang disetujui.
- [ ] Add tenant-owned persistence and mobile-friendly list/detail/create/edit flows; customer choices must come from the same workshop. / Tambahkan penyimpanan sesuai tenant serta alur daftar/detail/tambah/edit yang nyaman di HP; pilihan pelanggan wajib dari bengkel yang sama.
- [ ] Enforce `UNIQUE(workshop_id, normalized_plate_number)`; the same normalized plate may exist in different workshops. / Terapkan `UNIQUE(workshop_id, normalized_plate_number)`; pelat normalisasi sama boleh ada di bengkel berbeda.
- [ ] Test with/without a customer, normalization, duplicate plates within a workshop, plates shared across workshops, forbidden customer/vehicle IDs, and role authorization. / Uji dengan/tanpa pelanggan, normalisasi, pelat duplikat satu bengkel, pelat sama lintas bengkel, ID pelanggan/kendaraan terlarang, dan otorisasi role.
- [ ] Run migration, suite, build, and desktop/mobile checks; document the result and stop. / Jalankan pemeriksaan migration, suite, build, serta desktop/HP; catat hasil dan berhenti.

## C03 — Customer + Vehicle UX / Alur Pelanggan + Kendaraan

**EN:** Implement only when explicitly requested after C02. Make repeat visits quick: search a plate within the active workshop, open the matching vehicle if found, or start a new vehicle form if absent. Reuse C01/C02 validation, normalization, policies, and tenant boundaries.

**ID:** Implementasikan hanya saat diminta secara eksplisit setelah C02. Percepat kunjungan ulang: cari pelat pada bengkel aktif, buka kendaraan yang cocok jika ditemukan, atau mulai form kendaraan baru jika belum ada. Gunakan kembali validasi, normalisasi, policy, dan boundary tenant C01/C02.

- [ ] Implement the plate-search, found, and not-found paths without exposing other workshops' results. / Implementasikan alur pencarian pelat, hasil ditemukan, dan belum ditemukan tanpa membocorkan hasil bengkel lain.
- [ ] Keep forms usable with/without a customer and check desktop/mobile layouts, clear feedback, and input preservation. / Pastikan form dapat dipakai dengan/tanpa pelanggan; periksa layout desktop/HP, pesan yang jelas, dan isian yang dipertahankan.
- [ ] Test repeat-visit flows, plate normalization, role restrictions, tenant switching, and manipulated IDs; run the suite/build and record browser results. / Uji alur kunjungan ulang, normalisasi pelat, pembatasan role, pergantian tenant, dan manipulasi ID; jalankan suite/build serta catat hasil browser.

## Completion per C step / Penyelesaian setiap langkah C

**EN:** Follow the established workflow for each separately requested step: dedicated branch → implementation and relevant tests → documented validation and a new manual checklist section → focused commit/push → PR/review → merge to `main` under the session's instructions → annotated milestone tag → report and STOP. Preserve prior manual results; code checks do not mark laptop items complete. Record commit/tag and actual test counts only after execution.

**ID:** Ikuti alur yang sudah digunakan untuk setiap langkah yang diminta terpisah: branch khusus → implementasi dan tes relevan → dokumentasi validasi dan bagian checklist manual baru → commit/push terfokus → PR/review → merge ke `main` sesuai instruksi sesi → tag milestone beranotasi → laporkan dan BERHENTI. Pertahankan hasil manual lama; tes kode tidak mencentang item laptop. Catat commit/tag dan jumlah tes nyata hanya setelah dijalankan.

**EN:** Proposed tag names after successful completion are `c01-customer-management-v1`, `c02-vehicle-management-v1`, and `c03-customer-vehicle-ux-v1`. These tags do not exist as completed milestones in this plan. Existing F08 remains the Foundation code checkpoint; Git tags do not contain databases or `.env`.

**ID:** Rencana nama tag setelah selesai adalah `c01-customer-management-v1`, `c02-vehicle-management-v1`, dan `c03-customer-vehicle-ux-v1`. Tag tersebut belum menjadi milestone selesai pada rencana ini. F08 yang sudah ada tetap menjadi checkpoint kode Foundation; tag Git tidak memuat database atau `.env`.

**EN:** Work Orders, mechanic assignment, services, inventory, invoices, payments, and operational reports remain outside C01–C03. Stop at the requested C step; finishing the plan or one step does not authorize the next step.

**ID:** Work Order, assignment mekanik, jasa, inventory, invoice, pembayaran, dan laporan operasional tetap di luar C01–C03. Berhenti pada langkah C yang diminta; selesainya rencana atau satu langkah bukan izin melanjutkan langkah berikutnya.
