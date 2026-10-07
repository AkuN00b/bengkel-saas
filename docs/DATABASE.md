# BengkelOS — Database Design / Desain Database

> Version: 0.1  
> Database: PostgreSQL  
> ORM: Laravel Eloquent  
> Schema Management: Laravel Migrations  
> Primary Key: BIGINT Auto-Increment  
> Tenant Model: Shared Database, Shared Schema, Tenant-Owned Rows

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

## 14. Customers

Conceptual fields:

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

## 16. Vehicle Plate Normalization / Normalisasi Plat

Example:

    plate_number            = "B 1234 ABC"
    normalized_plate_number = "B1234ABC"

Normalization must be deterministic.

Recommended V1 uniqueness:

    UNIQUE(workshop_id, normalized_plate_number)

The same plate may exist independently in different workshop tenants.

## 17. Vehicle History / Riwayat Kendaraan

Vehicle is the main service-history anchor.

No ownership-history table is required in V1.

Changing current customer association must not rewrite old Work Order meaning.

## 18. Services

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

Conceptual fields:

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

## 24. Work Order Number / Nomor WO

Database ID and human-readable Work Order number are separate.

Potential tenant-local constraint:

    UNIQUE(workshop_id, number)

Keep numbering simple.

## 25. Work Order State Integrity / Integritas Status WO

Normal:

    draft → open → in_progress → completed

State transitions must be validated by business logic.

Do not accept arbitrary status mutation from requests.

## 26. Work Order Services

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
