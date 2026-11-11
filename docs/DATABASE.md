# BengkelOS — Database Design / Desain Database

> Version: 0.1  
> Database: PostgreSQL  
> ORM: Laravel Eloquent  
> Schema Management: Laravel Migrations  
> Primary Key: BIGINT Auto-Increment  
> Tenant Model: Shared Database, Shared Schema, Tenant-Owned Rows

### Sprint 5 reporting indexes / Indeks laporan Sprint 5 — 2026-11-11

**ID:** Migration `2026_11_11_010000_add_reporting_indexes` menambah `(workshop_id, paid_at, id)` pada payments, `(workshop_id, created_at, id)` pada payment_reversals, indeks parsial invoice berdasarkan issued_at/voided_at, serta `(workshop_id, vehicle_id, id)` untuk riwayat WO. Tidak ada tabel transaksi atau ledger baru. Rincian predikat, EXPLAIN, dan definisi piutang historis ada di [dokumen laporan](SPRINT_5_DASHBOARD_REPORTS.md). Seluruh 15 migration lokal Ran; record lama dipertahankan.

**EN:** One new migration adds tenant/time indexes for payments/reversals, partial invoice issue/void indexes, and the stable vehicle WO-history index. No transaction tables/ledgers are added. See the [report specification](SPRINT_5_DASHBOARD_REPORTS.md) for predicates, EXPLAIN validation, and historical balance rules. All 15 local migrations ran with existing records preserved.

## 1. Database Goals / Tujuan Database

Priorities:
1. tenant isolation
2. data integrity
3. correctness
4. historical accuracy
5. simplicity
6. maintainability
7. query performance

Do not model every possible future feature prematurely.

## 2. General Rules / Aturan Umum

Use PostgreSQL.

All schema changes use Laravel migrations.

Prefer:
- explicit foreign keys
- appropriate data types
- constraints for important invariants
- intentional nullable columns
- intentional indexes
- timestamps where useful

## 3. Primary Keys / Primary Key

Default:

    BIGINT auto-increment

Laravel:

    $table->id();

Security never depends on IDs being difficult to guess.

Public identifiers may be added separately when genuinely needed.

## 4. Tenant Strategy / Strategi Tenant

`workshops` represent tenants.

Operational records normally carry `workshop_id`.

Example:

    customers(workshop_id, ...)
    vehicles(workshop_id, ...)
    work_orders(workshop_id, ...)

Client-supplied workshop IDs are never proof of authorization.

## 5. Tenant Integrity / Integritas Tenant

Foreign keys alone do not prove related rows belong to the same tenant.

Application logic must reject cross-tenant relationships.

Database-level tenant-aware constraints may be added where practical.

## 6. High-Level Entity Map / Peta Entity

    User
      │
      ▼
    Workshop Membership
      │
      ▼
    Workshop
      ├── Subscription ── Plan
      ├── Customers ── Vehicles
      ├── Services
      ├── Spare Parts ── Inventory Movements
      └── Work Orders
             ├── Vehicle
             ├── Customer (optional)
             ├── Mechanic
             ├── Services
             ├── Spare Parts
             └── Invoice
                    ├── Invoice Items
                    └── Payments

Not all tables are implemented during Foundation.

## 7. Foundation Tables / Tabel Foundation

Foundation approximately requires:

    users
    workshops
    workshop_user
    plans
    subscriptions
    password_reset_tokens
    sessions

## 8. Users

Conceptual fields:

    users
    -----
    id
    name
    email
    email_verified_at
    password
    remember_token
    created_at
    updated_at

Rules:
- authentication email unique as required
- passwords stored as secure hashes
- do not put permanent tenant ownership directly on `users`

## 9. Workshops

Conceptual fields:

    workshops
    ---------
    id
    name
    slug
    phone NULLABLE
    address NULLABLE
    timezone
    created_at
    updated_at

Workshop is the primary SaaS tenant.

**EN — F03 implementation:** `workshops.id` uses BIGINT auto-increment. `name` and `slug` are required strings (255); `slug` has a global unique index, while names may repeat. `phone` is a nullable string (255), `address` nullable text, and `timezone` a required string (64) defaulting to `Asia/Jakarta` in both model and database. Standard timestamps are included. Slugs are explicitly supplied; no automatic slug or access grant is inferred from them.

**ID — Implementasi F03:** `workshops.id` memakai BIGINT auto-increment. `name` dan `slug` wajib berupa string (255); `slug` memiliki index unik global, sedangkan nama boleh sama. `phone` berupa string (255) opsional, `address` teks opsional, dan `timezone` string (64) wajib dengan default `Asia/Jakarta` pada model dan database. Timestamps standar disertakan. Slug diisi eksplisit; tidak ada pembuatan slug otomatis atau pemberian akses berdasarkan slug.

**EN:** Laravel remains configured to UTC. The PostgreSQL connection explicitly sets `timezone` to `UTC` in `config/database.php`, even if the server default differs (verify with `SHOW timezone`). The workshop timezone is display/business-date metadata; date conversion is not implemented by this schema. Validate IANA timezone input when workshop write endpoints are introduced. Relationship definitions use `workshop_user`; F04 implements the pivot and role persistence described below.

**ID:** Laravel tetap dikonfigurasi UTC. Koneksi PostgreSQL secara eksplisit menetapkan `timezone` ke `UTC` melalui `config/database.php`, meskipun default server berbeda (periksa melalui `SHOW timezone`). Timezone workshop merupakan metadata tampilan/tanggal bisnis; schema ini belum mengimplementasikan konversi waktu. Validasi input timezone IANA dilakukan saat endpoint penulisan workshop ditambahkan. Definisi relasi memakai `workshop_user`; F04 mengimplementasikan pivot dan penyimpanan role yang dijelaskan di bawah.

## 10. Workshop Membership

Preferred initial table:

    workshop_user
    -------------
    id
    workshop_id
    user_id
    role
    created_at
    updated_at

Workshop roles:
- owner
- cashier
- mechanic

Constraint:

    UNIQUE(workshop_id, user_id)

`super_admin` is platform-level, not an ordinary workshop role.

**EN — F04 implementation:** `workshop_user` is implemented with a required PostgreSQL role check (`owner`, `cashier`, `mechanic`), no role default, foreign keys that restrict user/workshop deletion, the unique pair above, and an index on `user_id` for reverse membership lookups. The `WorkshopMembership` pivot casts roles to `WorkshopRole`; membership checks always query the database. Roles have no implicit hierarchy or cross-workshop inheritance.

**ID — Implementasi F04:** `workshop_user` memiliki pemeriksaan role wajib di PostgreSQL (`owner`, `cashier`, `mechanic`), tanpa default role, foreign key yang membatasi penghapusan user/workshop, pasangan unik di atas, dan index `user_id` untuk pencarian membership dari user. Pivot `WorkshopMembership` melakukan cast ke `WorkshopRole`; pemeriksaan membership selalu membaca database. Role tidak memiliki hierarki implisit atau pewarisan lintas workshop.

**EN:** Platform administration is stored separately as `users.is_super_admin`, required boolean default false. Existing users remain non-admin. It is excluded from user mass assignment; public registration does not accept it or assign memberships. No seeded admin or implicit membership bypass is introduced. F04 supplies the data model and check helpers; future HTTP features still need server-side policies and tenant/resource checks.

**ID:** Administrasi platform disimpan terpisah sebagai `users.is_super_admin`, boolean wajib dengan default false. User lama tetap non-admin. Field ini tidak termasuk mass assignment user; registrasi publik tidak menerimanya atau memberikan membership. Tidak ada seed admin atau bypass membership implisit. F04 menyediakan model data dan helper pemeriksaan; fitur HTTP selanjutnya tetap memerlukan policy server serta pemeriksaan tenant/resource.


## 11. Active Workshop

`active_workshop_id` is runtime/session context, not permanent ownership.

    authenticated user
           ↓
    session.active_workshop_id
           ↓
    membership verification
           ↓
    active tenant context

Membership must be revalidated when resolving tenant context.

## 12. Plans

Conceptual fields:

    plans
    -----
    id
    code
    name
    price_monthly
    user_limit NULLABLE
    is_active
    created_at
    updated_at

Potential codes:
- trial
- starter
- pro

Pricing remains a product hypothesis.

## 13. Subscriptions

Conceptual fields:

    subscriptions
    -------------
    id
    workshop_id
    plan_id
    status
    starts_at
    trial_ends_at NULLABLE
    ends_at NULLABLE
    created_at
    updated_at

Potential states:
- trialing
- active
- expired
- cancelled

No payment-gateway fields in Foundation.

Potential indexes:
- `(workshop_id, status)`
- `(trial_ends_at)`

### F05 implementation / Implementasi F05

**EN:** The new plans migration inserts the catalog once: active free trial and inactive, unpriced starter/pro placeholders. `price_monthly` is nullable for unpublished pricing, otherwise a nonnegative BIGINT in Rupiah; active plans require a price. `user_limit` is nullable or positive. Plan codes are globally unique. No previously hypothetical paid prices are activated.

**ID:** Migration plans mengisi katalog sekali: trial gratis aktif serta placeholder starter/pro belum aktif dan belum berharga. `price_monthly` nullable untuk harga yang belum dipublikasikan, selain itu BIGINT Rupiah nonnegatif; paket aktif wajib memiliki harga. `user_limit` nullable atau positif. Kode paket unik global. Harga berbayar yang masih hipotesis tidak diaktifkan.

**EN:** Subscriptions have required workshop/plan foreign keys with restricted deletion, a checked status, UTC `timestamp with time zone` start/end fields, optional trial/end deadlines, and timezone-aware timestamps. A trialing row requires a trial deadline after its start; an end deadline cannot precede its start. Indexes cover workshop/status and trial expiry. A PostgreSQL partial unique index prevents multiple trialing/active rows for one workshop; expired/cancelled history may coexist. The time-check helper does not persist expiry transitions automatically.

**ID:** Subscription memiliki foreign key workshop/plan wajib dengan penghapusan dibatasi, status dengan constraint, field awal/akhir UTC `timestamp with time zone`, batas trial/akhir opsional, dan timestamps dengan timezone. Record trialing wajib memiliki batas trial setelah waktu mulai; batas akhir tidak boleh mendahului waktu mulai. Index mencakup workshop/status serta kedaluwarsa trial. Index unik parsial PostgreSQL mencegah beberapa record trialing/active pada satu workshop; riwayat expired/cancelled boleh tetap ada. Helper pemeriksaan waktu tidak menyimpan transisi kedaluwarsa otomatis.

**EN:** Owner registration accepts only validated identity/password/workshop-name fields. An active free trial plan is selected and share-locked server-side. User, workshop (default Asia/Jakarta), owner membership, and 14-day trial are inserted atomically; client IDs/status/dates are ignored. The old migrations and existing account/tenant data are unchanged, with no automatic trial backfill. Subscription HTTP access and active-tenant resolution are deferred to later tasks.

**ID:** Registrasi owner hanya menerima field identitas/password/nama bengkel yang tervalidasi. Server memilih paket trial gratis aktif dan memakai shared lock. User, workshop (default Asia/Jakarta), membership owner, serta trial 14 hari dibuat atomik; ID/status/tanggal dari client diabaikan. Migration lama dan data akun/tenant yang sudah ada tidak diubah, tanpa backfill trial otomatis. Akses HTTP subscription dan penentuan tenant aktif menyusul task berikutnya.

## 14. Customers

Implemented C01 fields / Field C01 yang diimplementasikan:

    customers
    ---------
    id
    workshop_id
    name
    phone NULLABLE
    notes NULLABLE
    created_at
    updated_at

Rules:
- tenant owned
- duplicate names allowed
- phone optional and not assumed globally unique
- customer is not required for vehicle creation

**EN — C01 implementation:** Migration `2026_11_09_000000_create_customers_table.php` adds BIGINT identity, a required workshop FK with `RESTRICT` on deletion, `name VARCHAR(255)` with a nonblank-name check, nullable `phone VARCHAR(32)` and `notes TEXT`, timestamps, and `(workshop_id, name, id)` indexing for tenant list ordering. Names and phones have no uniqueness constraint. Application validation limits notes to 5,000 characters. `Customer` belongs to `Workshop`; only name/phone/notes are fillable. HTTP writes derive workshop ownership server-side. C01 has no delete/archive flow or soft deletion; customer rows prevent workshop deletion while present. C02 now adds the vehicle relationship described below.

**ID — Implementasi C01:** Migration `2026_11_09_000000_create_customers_table.php` menambah ID BIGINT, FK bengkel wajib dengan `RESTRICT` saat hapus, `name VARCHAR(255)` dengan check nama tidak kosong, `phone VARCHAR(32)` dan `notes TEXT` nullable, timestamps, serta index `(workshop_id, name, id)` untuk pengurutan daftar tenant. Nama/telepon tidak diberi constraint unik. Validasi aplikasi membatasi catatan 5.000 karakter. `Customer` memiliki relasi ke `Workshop`; hanya nama/telepon/catatan yang fillable. Kepemilikan bengkel pada request tulis ditentukan server. C01 tidak memiliki alur hapus/arsip atau soft delete; record pelanggan mencegah penghapusan bengkel selama masih ada. C02 kini menambahkan relasi kendaraan yang dijelaskan di bawah.

## 15. Vehicles

Conceptual fields:

    vehicles
    --------
    id
    workshop_id
    customer_id NULLABLE
    plate_number
    normalized_plate_number
    brand NULLABLE
    model NULLABLE
    year NULLABLE
    notes NULLABLE
    created_at
    updated_at

Relationship:

    Customer 1 → 0..N Vehicles

Vehicle may exist without customer.

**EN — C02/C03 implementation, 2026-11-10:** `vehicles` has BIGINT identity and workshop FK, nullable customer ID, 20-character display/normalized plates, nullable 100-character brand/model, nullable SMALLINT year, notes TEXT, and timestamps. A composite FK `(workshop_id, customer_id)` references a supporting unique key `(workshop_id, id)` on customers and restricts parent deletion/key changes. This prevents cross-workshop references even outside HTTP. Vehicles also restrict workshop deletion. Indexes cover unique `(workshop_id, normalized_plate_number)` and customer lookups `(workshop_id, customer_id)`. There is no delete endpoint or cascading removal. Shared migration history is unchanged; rollback of the new migration removes vehicles and its supporting customer key only.

**ID — Implementasi C02/C03, 2026-11-10:** `vehicles` memuat ID BIGINT, FK bengkel, ID pelanggan nullable, pelat tampilan/normalisasi 20 karakter, merek/model nullable 100 karakter, tahun SMALLINT nullable, catatan TEXT, serta timestamps. FK gabungan `(workshop_id, customer_id)` merujuk kunci unik pendukung `(workshop_id, id)` pada pelanggan dan membatasi hapus/perubahan kunci induk. Ini mencegah referensi lintas bengkel bahkan di luar HTTP. Kendaraan juga membatasi penghapusan bengkel. Index mencakup keunikan `(workshop_id, normalized_plate_number)` dan pencarian pelanggan `(workshop_id, customer_id)`. Tidak ada endpoint hapus atau penghapusan berantai. Migration lama tidak diubah; rollback migration baru hanya menghapus kendaraan dan kunci pelanggan pendukungnya.

## 16. Vehicle Plate Normalization / Normalisasi Plat

Example:

    plate_number            = "B 1234 ABC"
    normalized_plate_number = "B1234ABC"

Normalization must be deterministic.

**EN:** The implemented normalizer accepts validated ASCII letters/digits/spaces, with at least one letter and digit. Display text is trimmed, uppercase, with single spaces; its normalized key removes spaces and is recomputed on every model save. Database checks require matching display/key values. Invalid punctuation, non-ASCII characters, and internal control bytes are rejected rather than silently discarded. HTTP limits: plate 20; brand/model 100; notes 5,000; year 1900 through next year (database ceiling 2100). Current customer association may change without creating history records; future Work Orders must preserve their own transaction context.

**ID:** Normalisasi yang tersedia menerima huruf ASCII/angka/spasi tervalidasi, wajib mengandung huruf dan angka. Tampilan dirapikan menjadi huruf besar dengan spasi tunggal; kunci normalisasi menghapus spasi dan dihitung ulang setiap model disimpan. Check database mewajibkan tampilan/kunci yang sesuai. Tanda baca tidak valid, karakter non-ASCII, serta byte kontrol internal ditolak, bukan dihapus diam-diam. Batas HTTP: pelat 20; merek/model 100; catatan 5.000; tahun 1900 sampai tahun depan (batas database 2100). Hubungan pelanggan saat ini dapat berubah tanpa tabel riwayat; Work Order mendatang harus menyimpan konteks transaksinya sendiri.

Recommended V1 uniqueness:

    UNIQUE(workshop_id, normalized_plate_number)

The same plate may exist independently in different workshop tenants.

## 17. Vehicle History / Riwayat Kendaraan

Vehicle is the main service-history anchor.

No ownership-history table is required in V1.

Changing current customer association must not rewrite old Work Order meaning.

## 18. Services

**ID — Sprint 3 diimplementasikan:** Migration `2026_11_10_120000_create_services_and_inventory` menambah lima tabel inventory/jasa dengan PK BIGINT tanpa mengubah migration lama. Jasa menyimpan nama, deskripsi opsional, default_price BIGINT nonnegatif, is_active, version positif, dan timestamp UTC. Referensi gabungan memakai unique `(workshop_id,id)`; daftar memiliki index `(workshop_id,is_active,id)`. Detail aturan ada di [Sprint 3](SPRINT_3_SERVICES_INVENTORY.md).

**EN — Sprint 3 implemented:** One new migration adds five service/inventory tables with BIGINT keys, preserving old migration history. Services store names, optional descriptions, nonnegative BIGINT default prices, activation, positive versions, and UTC timestamps. Composite references use `(workshop_id,id)` uniqueness; list filtering uses `(workshop_id,is_active,id)`. See [Sprint 3](SPRINT_3_SERVICES_INVENTORY.md).

Conceptual fields:

    services
    --------
    id
    workshop_id
    name
    description NULLABLE
    default_price
    is_active
    created_at
    updated_at

Changing `default_price` never changes historical transaction prices.

## 19. Spare Parts

**ID:** Spare part menambah SKU nullable/normalisasi trim-uppercase unik per bengkel, satuan tetap (default pcs), harga jual BIGINT, saldo awal 0, minimum opsional, keaktifan, dan versi. Saldo tidak pernah negatif; edit katalog tidak menerima current_stock. Tidak ada hard delete. Stock awal melalui movement restock. Satuan tidak dapat diganti melalui aplikasi.

**EN:** Parts have optional normalized tenant-unique SKUs, immutable units (default pcs), BIGINT selling prices/balances, zero opening stock, optional minima, activation, and versions. Balances cannot be negative; catalog writes exclude current_stock. No hard delete exists; opening stock uses restock movements.

Conceptual fields:

    spare_parts
    -----------
    id
    workshop_id
    sku NULLABLE
    name
    selling_price
    current_stock
    minimum_stock NULLABLE
    is_active
    created_at
    updated_at

Potential tenant-local SKU uniqueness:

    UNIQUE(workshop_id, sku)

with appropriate nullable handling.

## 20. Current Stock / Stok Saat Ini

Inventory history uses movements.

`current_stock` may be maintained as an operational balance for efficient reads.

Any stock-changing operation must update movement history and current balance atomically.

Never allow arbitrary stock changes that bypass movement history.

## 21. Inventory Movements

**ID — Field implementasi tambahan:** work_order_spare_part_id, source_movement_id, balance_before, balance_after, name_snapshot, sku_snapshot, unit_snapshot, operation_key UUID, dan request_fingerprint. Pelaku memakai created_by FK user. Kuantitas INTEGER positif; saldo BIGINT. FK gabungan mengikat movement ke tenant/barang/baris/WO yang sama dan retur ke sumber terkait. Unique partial menjamin satu work_order_usage per baris; `(workshop_id,operation_key)` mencegah posting ulang. Constraint memvalidasi tipe, arah saldo, alasan wajib, dan referensi penggunaan/retur. Trigger menolak UPDATE/DELETE movement. Index mendukung riwayat per barang dan pencarian retur berdasarkan source.

**EN — Additional implemented fields:** work_order_spare_part_id, source_movement_id, before/after balances, part snapshots, operation_key UUID, and request_fingerprint. created_by references users. Positive INTEGER quantities and BIGINT balances use composite FKs to match tenancy, part, WO line, and source. A partial unique index allows one usage per line; tenant operation keys prevent reposts. Checks enforce types, balances, reasons, and usage/return references. Triggers reject movement UPDATE/DELETE. Indexes support part histories and source-based returns.

**ID:** Action transaksi mengunci workshop → WO opsional → barang → baris/sumber. Memeriksa versi, stok terbaru, dan total retur di bawah lock; saldo, ledger, baris, dan event aktif atomik. Fingerprint mengikat replay pada pelaku/target/payload/versi, dengan otorisasi ulang. Koreksi tidak menghapus movement. Retur final menambah ledger saja tanpa mengubah record/event WO final.

**EN:** Actions lock workshop → optional WO → part → line/source, rechecking versions, balances, and cumulative returns under lock. Balances, movements, line state, and active-WO events commit atomically. Fingerprints bind replays to actor/targets/payload/versions after fresh authorization. Corrections never erase movements. Final returns append ledger entries without changing final WO records/events.

Conceptual fields:

    inventory_movements
    -------------------
    id
    workshop_id
    spare_part_id
    work_order_id NULLABLE
    type
    quantity
    reason NULLABLE
    created_by
    created_at

Initial types:
- restock
- work_order_usage
- work_order_return
- adjustment_in
- adjustment_out

Use positive quantity and let movement type define direction.

## 22. Inventory Rules / Aturan Inventory

Adding a part to a Work Order:

    NO STOCK CHANGE

Confirming usage:

    work_order_usage
        ↓
    stock decreases

Returning usable stock:

    work_order_return
        ↓
    stock increases

Do not delete original movements to correct history.

Work Order cancellation does not automatically restore used stock.

## 23. Work Orders

**ID — Sprint 2, 2026-11-10:** Diimplementasikan bersama [W01–W03 dan akun mekanik](SPRINT_2_WORK_ORDERS.md). Dua migration menambah `users.needs_password_setup` (default false), `workshop_user.is_active` (default true), `workshop_user.version` (default 1), serta `workshops.next_work_order_number` (default 1). Default mempertahankan akses akun lama. Relasi membership biasa mengabaikan membership nonaktif; daftar pengelolaan owner dapat melihatnya secara eksplisit.

**EN — Sprint 2, 2026-11-10:** Implemented with [W01–W03 and mechanic accounts](SPRINT_2_WORK_ORDERS.md). Two migrations add `users.needs_password_setup` (default false), `workshop_user.is_active` (default true), `workshop_user.version` (default 1), and `workshops.next_work_order_number` (default 1). Defaults preserve existing access. Ordinary membership relations ignore inactive memberships; the owner management list explicitly includes them.

Implemented fields / Field yang diimplementasikan:

    work_orders
    -----------
    id
    workshop_id
    vehicle_id
    customer_id NULLABLE
    assigned_mechanic_id NULLABLE
    number
    status
    complaint NULLABLE
    notes NULLABLE
    plate_number_snapshot NULLABLE
    customer_name_snapshot NULLABLE
    customer_phone_snapshot NULLABLE
    mechanic_name_snapshot NULLABLE
    cancellation_reason NULLABLE
    version DEFAULT 1
    opened_at NULLABLE
    started_at NULLABLE
    completed_at NULLABLE
    cancelled_at NULLABLE
    created_at
    updated_at

Required:
- workshop
- vehicle

Optional:
- customer
- assigned mechanic

Approved states:
- draft
- open
- in_progress
- completed
- cancelled

**ID:** FK gabungan `(workshop_id, vehicle_id)` dan `(workshop_id, customer_id)` membatasi referensi sesuai tenant. Referensi mekanik/aktor memakai FK user dengan penghapusan dibatasi agar riwayat dipertahankan. Unique `(workshop_id, number)` dan partial unique `(workshop_id, vehicle_id)` untuk Draft/Antre/Dikerjakan ditegakkan PostgreSQL. Index daftar `(workshop_id, status, id)`, penugasan `(workshop_id, assigned_mechanic_id, status)`, dan pelanggan `(workshop_id, customer_id)` mengikuti query aplikasi. Constraint memeriksa versi positif serta konsistensi status/timestamp.

**EN:** Composite vehicle/customer FKs enforce tenant references. Mechanic/actor user FKs restrict deletion to preserve history. PostgreSQL enforces unique workshop numbers and a partial unique vehicle/workshop index for draft/open/in-progress WOs. List, assignment, and customer indexes follow application queries. Checks enforce positive versions and lifecycle timestamp consistency.

### Work order events / Riwayat WO

    work_order_events
    -----------------
    id
    workshop_id
    work_order_id
    actor_id
    type
    details JSONB
    created_at TIMESTAMPTZ

**ID:** FK gabungan ke WO memastikan tenant riwayat konsisten. Event pembuatan, status, penugasan, dan perubahan detail ditulis atomik bersama WO. Trigger PostgreSQL menolak UPDATE/DELETE event. Tidak ada endpoint ubah/hapus riwayat. Index `(work_order_id, id)` mendukung riwayat berhalaman. Timestamp lifecycle dan riwayat UTC, ditampilkan memakai timezone bengkel.

**EN:** A composite WO FK keeps event tenancy consistent. Creation, status, assignment, and detail events are written atomically with the WO. PostgreSQL triggers reject event updates/deletions; no mutation endpoints exist. `(work_order_id, id)` supports paginated history. Lifecycle/event timestamps use UTC and display in workshop timezones.

## 24. Work Order Number / Nomor WO

Database ID and human-readable Work Order number are separate.

Potential tenant-local constraint:

    UNIQUE(workshop_id, number)

**ID:** Counter workshop dikunci sebelum alokasi nomor `WO-000001`, bertambah tanpa reset tanggal. Nomor, WO, dan event disimpan dalam satu transaksi. WO final tetap disimpan; nomor tidak dipakai ulang. Urutan lock operasi WO dan aktivasi mekanik selalu workshop terlebih dahulu, kemudian record terkait. Mutasi memakai versi untuk menolak form kedaluwarsa.

**EN:** The workshop counter is locked before allocating `WO-000001` numbers, with no date resets. Number allocation, WO, and events share a transaction. Final WOs remain stored and their numbers are never reused. WO and mechanic activation mutations lock the workshop before related records and use versions to reject stale forms.

## 25. Work Order State Integrity / Integritas Status WO

Normal:

    draft → open → in_progress → completed

State transitions must be validated by business logic.

Do not accept arbitrary status mutation from requests.

## 26. Work Order Services

**ID — Sprint 3:** work_order_services menyimpan referensi katalog wajib melalui FK gabungan tenant, name_snapshot, description_snapshot, quantity INTEGER, unit_price/subtotal BIGINT, status active/cancelled, dan timestamp UTC. Jumlah/harga diperiksa server terhadap overflow; constraint memastikan subtotal = quantity × unit_price. Baris dibatalkan tetap tersimpan; tidak ada jasa bebas/hard delete.

**EN — Sprint 3:** Service lines require tenant-scoped catalog references and store name/description snapshots, INTEGER quantities, BIGINT unit prices/subtotals, active/cancelled status, and UTC timestamps. Server checks prevent overflow; database checks verify multiplication. Cancelled lines remain stored; free-text services and hard deletes are unavailable.

Conceptual fields:

    work_order_services
    -------------------
    id
    workshop_id
    work_order_id
    service_id NULLABLE
    description
    quantity
    unit_price
    subtotal
    created_at
    updated_at

Snapshot fields are intentional.

Historical display must not depend on current master prices.

## 27. Work Order Spare Parts

**ID — Sprint 3:** work_order_spare_parts menyimpan referensi katalog wajib, name/sku/unit snapshots, quantity, unit_price, subtotal, dan status planned/used/cancelled. FK gabungan melindungi tenant. Quantity tetap setelah digunakan; perubahan harga saat aktif menaikkan versi WO dan membuat event tanpa movement. Retur diturunkan dari ledger sumber, bukan mengubah quantity/subtotal. Label Diretur sebagian/seluruhnya adalah informasi dari agregasi ledger. WO final dan snapshot baris tetap terkunci.

**EN — Sprint 3:** Part lines require catalog references and store name/SKU/unit snapshots, quantities, prices/subtotals, and planned/used/cancelled status. Composite FKs enforce tenancy. Used quantities lock; active price changes increment WO versions and append events without movements. Returns are ledger aggregates rather than quantity/subtotal rewrites. Partial/full-return labels derive from the ledger, preserving final lines and snapshots.

Conceptual fields:

    work_order_spare_parts
    ----------------------
    id
    workshop_id
    work_order_id
    spare_part_id NULLABLE
    description
    quantity
    unit_price
    subtotal
    usage_status
    created_at
    updated_at

Potential simple states:
- planned
- used
- returned

Inventory changes only through explicit usage/return operations.

## 28. Mechanic Assignment / Assignment Mekanik

V1 supports one primary mechanic per Work Order.

Referenced mechanic must belong to the active workshop and have the appropriate role/access model.

Do not implement complex mechanic scheduling or multi-mechanic allocation without validation.

## 29. Invoices

**ID — Sprint 4:** Migration `2026_11_11_000000_create_invoices_and_payments` menambah lima tabel billing dan workshop.next_invoice_number tanpa mengubah migration lama. Invoices menyimpan workshop_id, work_order_id, replaces_invoice_id opsional, number nullable sampai terbit, status, total BIGINT, versi, fingerprint sumber, snapshot WO/pelat/pelanggan/bengkel/kontak, catatan, issued/void actor dan UTC. Partial unique menjaga satu draft/issued per WO, unique nomor per tenant. Referensi pengganti wajib tenant/WO yang sama. Tidak ada discount_total: total adalah penjumlahan snapshot tanpa diskon/pajak terpisah. Field konseptual di bawah bukan daftar kolom final.

**EN — Sprint 4:** One migration adds five billing tables and a workshop counter, preserving previous migrations. Invoices store scoped WO/predecessor references, nullable pre-issue numbers, status, BIGINT total, version, source fingerprint, document/contact snapshots, notes, issue/void actors and UTC times. Partial uniqueness permits one draft/issued invoice per WO, with tenant-unique official numbers and same-WO predecessors. No separate discount/tax columns are implemented. Conceptual fields below are not the final column list.

**ID:** Trigger menolak DELETE dokumen, mutasi dokumen void, perubahan snapshot invoice terbit, issue untuk WO belum Selesai/total item tidak cocok, serta void jika ada pembayaran valid. Versi/timestamp operasional tetap dapat bertambah untuk transaksi pembayaran. Alokasi nomor hanya saat issue dengan lock workshop, tanpa reset/reuse. Detail keputusan: [Sprint 4](SPRINT_4_INVOICES_PAYMENTS.md).

**EN:** Triggers prevent invoice deletion, void-document edits, issued snapshot rewrites, issuance from unfinished WOs/inconsistent totals, and voiding valid payments. Versions/timestamps may advance for payment operations. Number allocation occurs only at issue under workshop locks, without resets/reuse. See [Sprint 4](SPRINT_4_INVOICES_PAYMENTS.md).

Conceptual fields:

    invoices
    --------
    id
    workshop_id
    work_order_id
    number
    status
    subtotal
    discount_total
    grand_total
    issued_at NULLABLE
    voided_at NULLABLE
    created_at
    updated_at

States:
- draft
- issued
- void

Potential constraint:

    UNIQUE(workshop_id, number)

Payment condition is separate from invoice document state.

## 30. Invoice Items

**ID — Implementasi:** invoice_items menyimpan invoice/workshop/WO, item_type service/spare_part, FK sumber bertipe work_order_service_id atau work_order_spare_part_id (tepat satu), name/description/unit snapshots, quantity INTEGER, unit_price/subtotal BIGINT, price_reason opsional, dan UTC. FK gabungan mengikat sumber ke WO invoice yang sama. Constraint menguji kuantitas/harga/subtotal. Trigger hanya mengizinkan INSERT/UPDATE/DELETE saat parent Draft; refresh draft direkam before/after pada invoice_events. Tidak ada item bebas.

**EN — Implemented:** Invoice items have typed service/part WO-line FKs (exactly one), scoped invoice/WO references, name/description/unit snapshots, integer quantities/prices/subtotals, optional override reasons, and UTC timestamps. Composite FKs match the invoice WO. Checks enforce arithmetic. Triggers allow line mutations only while the parent is draft; refresh before/after data is retained in events. Arbitrary lines are unavailable.

Conceptual fields:

    invoice_items
    -------------
    id
    workshop_id
    invoice_id
    item_type
    reference_id NULLABLE
    description
    quantity
    unit_price
    subtotal
    created_at
    updated_at

Potential types:
- service
- spare_part
- other

Historical invoices depend on snapshots, not current master data.

## 31. Money Data Type / Tipe Data Uang

Never use floating point for money.

For V1 Indonesian Rupiah, store integer Rupiah values.

Example:

    Rp25.000 → 25000

Use sufficiently large integer types.

If fractional/multi-currency requirements appear later, review the design explicitly.

## 32. Payments

**ID — Implementasi:** payments append-only berisi workshop_id, invoice_id, amount BIGINT positif, method cash/bank_transfer/other, reference/note opsional (note wajib untuk other), created_by, paid_at UTC server. payment_reversals append-only mengikat tepat satu pembatalan penuh per payment, beserta tenant/invoice, actor, reason, created_at. Tidak ada edit, hard delete, partial reversal, backdate, atau record refund uang.

**EN — Implemented:** Append-only payments store scoped invoice references, positive BIGINT amounts, cash/bank_transfer/other methods, optional references/notes (other requires a note), creator, and server UTC time. Append-only reversals permit one full record reversal per payment, retaining invoice/tenant, actor, reason, and time. Editing, hard deletion, partial reversals, backdating, and money-refund records are unavailable.

**ID:** Trigger insert mengunci invoice dan memeriksa status Terbit/sisa tagihan, sehingga pembayaran berlebih tetap ditolak di database. Status/sisa dihitung dari pembayaran tanpa reversal. invoice_events append-only menyimpan actor, type, details JSONB, operation_key UUID unik per tenant, fingerprint request, dan UTC. Replay tetap memeriksa otorisasi; kunci yang sama dengan target/payload/pelaku berbeda ditolak. Index mendukung daftar tenant/status serta item/payment/event berhalaman.

**EN:** Insert triggers lock invoices and enforce issued state/outstanding balances, preventing database overpayment. Status/balance derives from non-reversed payments. Append-only events store actor, type, JSONB details, tenant-unique operation keys, request fingerprints, and UTC. Replays reauthorize and reject changed actors/targets/payloads. Indexes support scoped lists and paginated lines/payments/events.

Conceptual fields:

    payments
    --------
    id
    workshop_id
    invoice_id
    amount
    method
    reference NULLABLE
    note NULLABLE
    paid_at
    created_by
    created_at
    updated_at

Initial methods:
- cash
- bank_transfer
- other

Relationship:

    Invoice 1 → 0..N Payments

## 33. Payment Condition / Kondisi Pembayaran

Prefer deriving:
- unpaid
- partially_paid
- paid

from invoice total versus valid payment total.

Avoid redundant stored payment status until demonstrated necessary.

## 34. Payment Integrity / Integritas Payment

Payments are financial history.

Do not casually hard-delete them.

Incorrect payments should eventually use explicit correction/void/reversal behavior rather than history deletion.

Full refund infrastructure is outside V1.

## 35. Financial Snapshot / Snapshot Finansial

Master-data changes never rewrite historical prices.

Example:

    service today = 25000
    invoice #100 = 25000

Later master price:

    service = 30000

Invoice #100 remains:

    25000

Same rule applies to spare parts.

## 36. Foreign-Key Deletion / Penghapusan FK

Do not blindly cascade-delete historical data.

Deleting/deactivating master records must not destroy:
- Work Orders
- invoices
- payments
- inventory history

Prefer prevent-delete, inactive state, nullable reference with snapshot, or soft deletion where genuinely justified.

## 37. Soft Delete / Soft Delete

Do not add `SoftDeletes` to every table automatically.

Use explicit business states and reversal/correction records for financial and inventory history.

Master data may often use `is_active`.

## 38. Timestamps / Timestamp

Use `created_at` and `updated_at` on normal mutable records.

Use explicit business-event timestamps:
- started_at
- completed_at
- cancelled_at
- issued_at
- paid_at
- voided_at

Do not interpret `updated_at` as a specific business event.

## 39. Timezone / Timezone

Use a consistent backend timezone strategy and convert for workshop/user display.

Workshop timezone should be available when business-date interpretation requires it.

Document the exact Laravel/PostgreSQL configuration during implementation.

## 40. Index Strategy / Strategi Index

Potential examples:

    customers(workshop_id, name)
    vehicles(workshop_id, normalized_plate_number)
    work_orders(workshop_id, status)
    work_orders(workshop_id, created_at)
    work_orders(workshop_id, vehicle_id)
    work_orders(workshop_id, customer_id)
    spare_parts(workshop_id, is_active)
    inventory_movements(workshop_id, spare_part_id, created_at)
    invoices(workshop_id, status)
    invoices(workshop_id, created_at)

Do not index every possible column upfront.

## 41. Composite Index Order / Urutan Composite Index

For:

    WHERE workshop_id = ?
      AND status = ?
    ORDER BY created_at DESC

a potential index is:

    (workshop_id, status, created_at)

Actual query behavior and plans should guide optimization.

## 42. Unique Constraints / Unique Constraint

Potential important constraints:

    users.email

    workshop_user
    (workshop_id, user_id)

    vehicles
    (workshop_id, normalized_plate_number)

    work_orders
    (workshop_id, number)

    invoices
    (workshop_id, number)

Do not implement tenant-local uniqueness globally.

## 43. Nullable Columns / Nullable Column

Nullable must represent a real business possibility.

Approved examples:
- `vehicles.customer_id`
- `work_orders.customer_id`
- `work_orders.assigned_mechanic_id`

Do not make columns nullable merely to avoid validation design.

## 44. Database Transactions / Database Transaction

Use transactions for atomic operations.

Owner registration:

    BEGIN
    create user
    create workshop
    create owner membership
    create trial subscription
    COMMIT

Inventory usage:

    BEGIN
    validate stock
    create movement
    update current_stock
    mark usage
    COMMIT

Payment:

    BEGIN
    validate invoice
    create payment
    validate financial state
    COMMIT

## 45. Concurrency / Concurrent Operations

Critical stock/payment operations must not assume read-modify-save is always safe.

Example stock race:

    Stock = 1
    Request A reads 1
    Request B reads 1
    A consumes 1
    B consumes 1

Use appropriate PostgreSQL/Laravel transactional locking or atomic updates when implementing critical operations.

## 46. Query Rules / Aturan Query

Tenant-owned queries must include active tenant scope.

Use eager loading intentionally.

Paginate large collections.

Do not load unlimited operational history into memory.

## 47. Data Exposure / Eksposur Data

IDs are not authorization.

Resource access requires:
- authentication
- tenant membership
- authorization
- resource ownership

ID enumeration must never expose another tenant's data.

## 48. Migration Rules / Aturan Migration

Once migrations are shared or used in persistent environments, do not casually rewrite them.

Create new migrations for schema changes.

Keep migrations reversible when reasonably practical.

Never put secrets inside migrations.

## 49. Seeder & Factory Rules / Aturan Seeder & Factory

Factories/seeders support tests and local development.

Test data should make tenant boundaries obvious.

Never seed real credentials or private customer information.

## 50. Foundation Implementation Boundary / Batas Foundation

This document describes intended database direction.

Foundation should initially implement only approximately:

    users
    workshops
    workshop_user
    plans
    subscriptions
    password_reset_tokens
    sessions

Then stop.

Operational tables are introduced according to ROADMAP.md.

## 51. Database Review Checklist / Checklist Database

Before a significant schema change, ask:
1. Which workshop owns this data?
2. Does it need `workshop_id`?
3. What are the foreign keys?
4. Can cross-tenant relationships occur?
5. Which fields are legitimately nullable?
6. What must be unique?
7. Is uniqueness tenant-local or global?
8. What queries are common?
9. Which indexes support them?
10. Is historical data preserved?
11. Can deletion destroy important history?
12. Does the operation require a transaction?
13. Can concurrent requests corrupt it?
14. Are money values using safe types?
15. Are historical transactions independent from mutable master data?

## 52. Final Database Principle / Prinsip Database Akhir

**EN:** The database should tell the truth about workshop operations. Protect tenant boundaries, inventory history, and financial history. Keep the schema simple and add complexity only when real requirements justify it.

**ID:** Database harus menggambarkan operasional bengkel secara benar. Lindungi boundary tenant, history inventory, dan history finansial. Jaga schema tetap sederhana dan tambahkan kompleksitas hanya ketika kebutuhan nyata membenarkannya.
