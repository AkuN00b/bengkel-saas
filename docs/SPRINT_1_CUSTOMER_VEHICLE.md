# Sprint 1 — Customer + Vehicle / Pelanggan + Kendaraan

> Recorded / Dicatat: 2026-11-09
>
> Status: C01 implemented and validated; C02–C03 remain planned / C01 diimplementasikan dan divalidasi; C02–C03 tetap rencana.

**EN:** This document expands [ROADMAP.md, Sprint 1](ROADMAP.md#15-sprint-1--customer--vehicle). Foundation F01–F08 code validation is complete at tag `f08-foundation-hardening-v1`; the [Foundation review](FOUNDATION_REVIEW.md) and existing [manual results](MANUAL_TESTING.md) remain separate. C01 was explicitly authorized on 2026-11-09. Start each later C step only when explicitly requested, then stop after that step.

**ID:** Dokumen ini merinci [ROADMAP.md, Sprint 1](ROADMAP.md#15-sprint-1--customer--vehicle). Validasi kode Foundation F01–F08 selesai pada tag `f08-foundation-hardening-v1`; [review Foundation](FOUNDATION_REVIEW.md) dan [hasil manual](MANUAL_TESTING.md) tetap dicatat terpisah. C01 diizinkan secara eksplisit pada 2026-11-09. Mulai langkah C berikutnya hanya saat diminta secara eksplisit, lalu berhenti setelah langkah tersebut selesai.

## Step order and progress / Urutan langkah dan progres

| Step / Langkah | Outcome / Hasil yang dituju | Current status / Status saat ini |
| --- | --- | --- |
| C01 — Customer Management | Add, view, edit, and search workshop customers / Tambah, lihat, edit, dan cari pelanggan bengkel | Implemented; 221 tests / 1,069 assertions pass; laptop pending / Diimplementasikan; 221 tes / 1.069 assertion lolos; laptop belum dikonfirmasi |
| C02 — Vehicle Management | Record vehicles with an optional customer / Catat kendaraan dengan pelanggan opsional | Planned after C01 / Direncanakan setelah C01 |
| C03 — Customer + Vehicle UX | Search plate → open existing vehicle or register a new one / Cari pelat → buka kendaraan yang ada atau daftarkan baru | Planned after C02 / Direncanakan setelah C02 |

## C01 — Customer Management / Manajemen Pelanggan

**EN:** Start with the customer module only. Use the existing active-workshop context, Blade/Tailwind shell, Laravel validation and server-side authorization. Conceptual fields follow [DATABASE.md, Customers](DATABASE.md#14-customers): ID, workshop, name, optional phone/notes, and timestamps. Duplicate names are allowed; phone is not assumed globally unique.

**ID:** Mulai dari modul pelanggan saja. Gunakan konteks bengkel aktif, shell Blade/Tailwind, validasi Laravel, dan otorisasi server yang tersedia. Field konseptual mengikuti [DATABASE.md, Customers](DATABASE.md#14-customers): ID, bengkel, nama, telepon/catatan opsional, serta timestamps. Nama boleh sama; telepon tidak dianggap unik secara global.

Implementation steps / Langkah implementasi:

- [x] Settle the access matrix and deletion behavior below; record the decision before coding. / Tetapkan matriks akses dan perilaku penghapusan di bawah; catat keputusan sebelum coding.
- [x] Add the customer migration/model and test factory using existing database conventions; preserve shared migrations. / Tambahkan migration/model pelanggan dan factory tes sesuai convention database; pertahankan migration yang sudah dibagikan.
- [x] Implement a paginated list, detail, create/edit forms, and search within the verified active workshop. / Implementasikan daftar berhalaman, detail, form tambah/edit, dan pencarian pada bengkel aktif terverifikasi.
- [x] Enforce the chosen role rules on every endpoint; derive `workshop_id` server-side and scope all record lookups. / Terapkan aturan role yang dipilih pada setiap endpoint; tentukan `workshop_id` di server dan batasi seluruh pencarian record sesuai tenant.
- [x] Test validation, allowed/denied roles, guest access, revoked membership, forged IDs/tenant fields, A/B isolation, duplicate names, and escaped output. / Uji validasi, role diizinkan/ditolak, tamu, membership dicabut, manipulasi ID/field tenant, isolasi A/B, nama sama, dan escaping output.
- [x] Check customer flows on desktop/mobile, migration behavior, the relevant/full suite, and frontend build; record actual results. / Periksa alur pelanggan pada desktop/HP, perilaku migration, suite relevan/penuh, dan build frontend; catat hasil nyata.

### Agreed C01 access / Akses C01 yang disepakati

**EN:** Agreed on 2026-11-09 when C01 was requested: owner/cashier create and edit, mechanic reads/searches only. The user explicitly selected no deletion feature for C01. Vehicle-specific permissions remain to be settled for C02.

**ID:** Disepakati pada 2026-11-09 saat C01 diminta: owner/kasir menambah dan mengedit, mekanik hanya melihat/mencari. User secara eksplisit memilih tanpa fitur hapus pada C01. Izin khusus kendaraan tetap ditetapkan untuk C02.

| Workshop role / Role bengkel | View/search customers / Lihat/cari pelanggan | Add/edit customers / Tambah/edit pelanggan | Delete / Hapus |
| --- | --- | --- | --- |
| `owner` | Allowed / Boleh | Allowed / Boleh | Unavailable / Tidak tersedia |
| `cashier` | Allowed / Boleh | Allowed / Boleh | Unavailable / Tidak tersedia |
| `mechanic` | Read only / Hanya baca | Denied / Ditolak | Unavailable / Tidak tersedia |

**EN:** Access is limited to the active workshop where membership is currently valid. Platform `super_admin` has no tenant-membership bypass. C01 provides no delete endpoint/button, soft deletion, or cascading removal. Tests cover create/read/update and rejection of deletion attempts. Any later deletion/archive design requires its own decision and must preserve vehicle/service history.

**ID:** Akses terbatas pada bengkel aktif dengan membership yang masih valid. `super_admin` platform tidak memperoleh bypass membership tenant. C01 tidak menyediakan endpoint/tombol hapus, soft delete, atau penghapusan berantai. Tes mencakup tambah/lihat/edit serta penolakan percobaan hapus. Desain hapus/arsip berikutnya memerlukan keputusan sendiri dan wajib menjaga riwayat kendaraan/servis.

### C01 validation record / Catatan validasi C01 — 2026-11-09

**EN:** The implementation uses six resource actions (index, show, create, store, edit, update) under `auth`, `auth.session`, and `active.workshop`, with `CustomerPolicy` authorization. `CustomerRequest` checks the shared owner/cashier write permission before validating create/update; updates also authorize the scoped record. `workshop_id` and other non-allowlisted fields cannot be assigned through HTTP. Customer IDs are validated before querying to reject BIGINT overflow safely. Searches group name/phone conditions under the workshop filter, escape `%`, `_`, and backslash, use bound parameters, and paginate 20 rows in name/ID order. Limits: name 255, phone 32, notes 5,000, search 100 characters; phone accepts digits and common punctuation. Null bytes are rejected before PostgreSQL queries. Missing/foreign records return 404 to authorized writers/readers; denied writers receive 403. There is no deletion route.

**ID:** Implementasi memakai enam aksi resource (index, show, create, store, edit, update) di bawah `auth`, `auth.session`, dan `active.workshop`, dengan otorisasi `CustomerPolicy`. `CustomerRequest` memeriksa izin tulis bersama owner/kasir sebelum validasi tambah/edit; update juga mengotorisasi record sesuai tenant. `workshop_id` dan field di luar allowlist tidak dapat diisi melalui HTTP. ID pelanggan divalidasi sebelum query agar overflow BIGINT ditolak aman. Pencarian mengelompokkan kondisi nama/telepon di bawah filter bengkel, meng-escape `%`, `_`, dan backslash, memakai parameter binding, serta menampilkan 20 baris per halaman menurut nama/ID. Batas: nama 255, telepon 32, catatan 5.000, pencarian 100 karakter; telepon menerima angka dan tanda baca umum. Null byte ditolak sebelum query PostgreSQL. Record hilang/asing menghasilkan 404 bagi pembaca/penulis sah; penulis tanpa izin mendapat 403. Route hapus tidak tersedia.

| Check / Pemeriksaan | Result / Hasil |
| --- | --- |
| PHP 8.4.27 / PostgreSQL 17, `php artisan test --compact` | PASS — 221 tests / tes; 1,069 assertions / assertion, including Foundation regressions / termasuk regresi Foundation |
| Customer tests / Tes pelanggan | PASS — role restrictions, A/B read/write isolation, tampered IDs/ownership, revoked/downgraded membership, active-workshop switching, guest/no-membership access, validation, XSS/SQL input, literal search, pagination, no deletion / pembatasan role, isolasi baca/tulis A/B, manipulasi ID/kepemilikan, pencabutan/penurunan role, pergantian tenant, tamu/tanpa membership, validasi, input XSS/SQL, pencarian literal, pagination, tanpa hapus |
| Database / migration | PASS — required fields, nonblank name, FK/restricted deletion, mass assignment; isolated upgrade from 8 F08 migrations to 9 preserves existing user/workshop/membership/trial rows; rollback/reapply passes / field wajib, nama tidak kosong, FK/pembatasan hapus, mass assignment; upgrade terpisah dari 8 migration F08 ke 9 menjaga record user/bengkel/membership/trial; rollback/penerapan ulang lolos |
| Fresh migrations/seed on disposable DB / pada database sementara | PASS — 9 migrations, 3 plans, 0 users/customers / 9 migration, 3 paket, 0 user/pelanggan |
| Pint, `composer validate --strict`, `php artisan view:cache`, `npm run build` | PASS |
| Chromium 1280×900 and 390×844 / desktop dan HP | PASS — add/edit, validation with preserved input, optional fields, literal search, pagination, long names, A/B isolation, direct mechanic denials, cashier writes, CSRF, no delete, switching/revocation; no JS exceptions or horizontal overflow / tambah/edit, validasi dengan isian dipertahankan, field opsional, pencarian literal, pagination, nama panjang, isolasi A/B, penolakan langsung mekanik, penulisan kasir, CSRF, tanpa hapus, pergantian/pencabutan; tanpa error JS atau scroll horizontal |

**EN:** No dependencies were added or updated. Docker was only a temporary verification runtime. Shared migrations and previous manual checkmarks are preserved. Apply the new migration using `php artisan migrate`; do not rebuild application data. Rolling back the customer migration drops its table and customer records, so rollback checks were restricted to a disposable database. [C01 laptop checks](MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01) remain unchecked until performed locally; real SMTP is outside this change. C02/C03 are not implemented.

**ID:** Tidak ada dependency ditambah/diperbarui. Docker hanya runtime verifikasi sementara. Migration lama dan centang manual terdahulu dipertahankan. Terapkan migration baru melalui `php artisan migrate`; jangan membangun ulang data aplikasi. Rollback migration pelanggan menghapus tabel dan record pelanggan sehingga pemeriksaannya dibatasi pada database sementara. [Tes laptop C01](MANUAL_TESTING.md#c01-laptop-follow-up--pemeriksaan-laptop-c01) tetap kosong sampai dicoba secara lokal; SMTP nyata di luar perubahan ini. C02/C03 belum diimplementasikan.

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

**EN:** C01 targets milestone tag `c01-customer-management-v1` after merge. Proposed future tags are `c02-vehicle-management-v1` and `c03-customer-vehicle-ux-v1`; C02/C03 remain unimplemented. Existing F08 remains the Foundation code checkpoint; Git tags do not contain databases or `.env`.

**ID:** C01 menargetkan tag milestone `c01-customer-management-v1` setelah merge. Rencana tag berikutnya adalah `c02-vehicle-management-v1` dan `c03-customer-vehicle-ux-v1`; C02/C03 belum diimplementasikan. F08 yang sudah ada tetap menjadi checkpoint kode Foundation; tag Git tidak memuat database atau `.env`.

**EN:** Work Orders, mechanic assignment, services, inventory, invoices, payments, and operational reports remain outside C01–C03. Stop at the requested C step; finishing the plan or one step does not authorize the next step.

**ID:** Work Order, assignment mekanik, jasa, inventory, invoice, pembayaran, dan laporan operasional tetap di luar C01–C03. Berhenti pada langkah C yang diminta; selesainya rencana atau satu langkah bukan izin melanjutkan langkah berikutnya.
