# BengkelOS — Architecture / Arsitektur

> Version: 0.1  
> Stack: Laravel + Blade + Tailwind CSS + Alpine.js + PostgreSQL  
> Architecture Style: Modular Monolith  
> Tenant Model: Shared Database, Shared Schema, Tenant-Owned Rows

## 1. Architecture Goal / Tujuan Arsitektur

**EN:** Build a secure, understandable SaaS architecture that supports BengkelOS without premature distributed-system complexity.

**ID:** Bangun arsitektur SaaS yang aman dan mudah dipahami untuk BengkelOS tanpa kompleksitas distributed system secara prematur.

## 2. Architecture Style / Gaya Arsitektur

Use a modular monolith:
- one Laravel application
- one PostgreSQL database initially
- server-rendered UI
- clear business boundaries inside the application
- Laravel conventions first

## 3. What We Are NOT Building / Yang Tidak Kita Bangun

Do not introduce without explicit need:
- microservices
- separate frontend/API applications
- Kubernetes
- Kafka/RabbitMQ
- Elasticsearch
- mandatory Redis
- CQRS/event sourcing
- generic repository layers
- excessive DTO abstraction

## 4. High-Level Application Structure / Struktur Aplikasi Tingkat Tinggi

Conceptual boundaries:

    Foundation
    ├── Authentication
    ├── Workshops
    ├── Memberships
    ├── Authorization
    └── Subscription

    Workshop Operations
    ├── Customers
    ├── Vehicles
    ├── Work Orders
    ├── Mechanics
    ├── Services
    ├── Spare Parts
    ├── Inventory
    ├── Invoices
    ├── Payments
    └── Reports

These are module boundaries inside one Laravel application, not microservices.

## 5. Module Boundaries / Batas Modul

Keep business concerns understandable and separated where useful, but do not create framework-like abstraction for every module.

Cross-module operations may coordinate through application/service/action classes when the business transaction justifies it.

## 6. Laravel Structure / Struktur Laravel

Prefer Laravel conventions:
- Models
- Controllers
- Form Requests
- Policies/Gates
- middleware
- Eloquent relationships
- Jobs only when needed
- service/action classes only for non-trivial business logic

## 7. Request Flow / Alur Request

Typical protected request:

    Authentication
         ↓
    Resolve Active Workshop
         ↓
    Verify Membership
         ↓
    Authorization
         ↓
    Validation
         ↓
    Business Logic
         ↓
    DB Transaction when needed
         ↓
    Response

## 8. Multi-Tenant Architecture / Arsitektur Multi-Tenant

BengkelOS uses:

> Shared application + shared database + shared schema + tenant-owned rows.

`workshops` represent tenants.

Tenant-owned operational records normally include `workshop_id`.

## 9. Tenant Resolution / Penentuan Tenant

Never trust a client-supplied `workshop_id` as proof of access.

Tenant context comes from the authenticated user, valid membership, and trusted server-side active-workshop context.

## 10. Active Workshop Session / Session Workshop Aktif

Approved V1 strategy:

    session.active_workshop_id

Login flow:

    LOGIN
      ↓
    load memberships
      ↓
    one workshop?
      ├── yes → activate automatically
      └── no  → user selects workshop
                    ↓
              verify membership
                    ↓
              save active_workshop_id

`session.active_workshop_id` is not authorization proof.

Whenever active tenant context is resolved, verify that the authenticated user still has valid membership.

If membership is revoked or the workshop is invalid:
- clear the active-workshop session
- deny access or require workshop selection again

Workshop switching must verify membership before updating the session.

## 11. Tenant Query Rules / Aturan Query Tenant

Tenant-owned queries must be scoped to the active workshop.

Prefer conceptually:

    activeWorkshop
        ->workOrders()
        ->findOrFail($id)

instead of unrestricted global resource lookup followed by assumptions.

Tenant A must never access Tenant B data through IDs, URLs, requests, exports, or future APIs.

## 12. Membership Model / Model Membership

Users and workshops use a many-to-many membership model.

Conceptually:

    User
      │
      └── Workshop Membership
                 │
                 └── Workshop

Do not put one permanent `workshop_id` on `users` as the tenant architecture.

## 13. Authentication / Autentikasi

Use Laravel-supported authentication mechanisms.

Required Foundation flows:
- register
- login
- logout
- forgot password
- reset password

Do not implement custom password cryptography.

## 14. Authorization / Otorisasi

Authentication does not imply authorization.

Use server-side Policies, Gates, middleware, or equivalent Laravel mechanisms.

Sensitive actions verify:
- user role/permission
- tenant membership
- resource ownership

Frontend visibility is never a security boundary.

## 15. Roles / Role

Initial roles:
- `super_admin`
- `owner`
- `cashier`
- `mechanic`

Avoid a complex dynamic permission builder in V1.

`super_admin` is platform-level and must not be confused with an ordinary workshop membership role.

## 16. Input Validation / Validasi Input

Treat all external input as untrusted.

Validate server-side:
- required fields
- types
- lengths
- formats
- enums
- allowed IDs
- tenant ownership
- file types/sizes when uploads exist
- sorting/filtering allowlists when applicable

Frontend validation exists for UX only.

## 17. Business Logic Placement / Penempatan Business Logic

Keep controllers focused on HTTP concerns.

Simple CRUD logic may remain straightforward.

Extract complex operations when they involve:
- multiple records
- important state transitions
- inventory
- financial operations
- reusable business rules
- meaningful test boundaries

Do not create abstraction merely for architectural appearance.

## 18. Database Transactions / Transaksi Database

Use database transactions for atomic multi-record operations.

Examples:
- owner registration provisioning
- inventory usage
- stock return
- invoice issue when multiple records change
- payment recording when related state must remain consistent

## 19. Work Order Architecture / Arsitektur Work Order

Approved states:

    draft
    open
    in_progress
    completed
    cancelled

Normal flow:

    draft → open → in_progress → completed

Status transitions must be controlled business operations.

`completed` is not equivalent to `paid`.

## 20. Customer & Vehicle Architecture / Arsitektur Customer & Vehicle

Relationship:

    Customer 1 → 0..N Vehicles

Vehicle may exist without customer.

For service Work Orders:
- workshop required
- vehicle required
- customer optional

Vehicle is the primary anchor for service history.

Historical Work Orders retain transaction context even when current vehicle/customer association changes.

## 21. Inventory Architecture / Arsitektur Inventory

Selecting a spare part on a Work Order does not change stock.

Approved flow:

    planned
      ↓
    confirm used
      ↓
    inventory movement OUT
      ↓
    stock decreases

Returns use explicit movement IN.

Cancellation does not automatically restore used stock.

Inventory history must remain auditable.

## 22. Invoice Architecture / Arsitektur Invoice

Document lifecycle:

    draft
    issued
    void

Draft invoices may change.

Issued invoices are official records and must not be silently materially rewritten.

V1 may use controlled void + reissue for material correction.

## 23. Payment Architecture / Arsitektur Pembayaran

Relationship:

    Invoice 1 → 0..N Payments

Payment condition is separate from invoice document state:

    unpaid
    partially_paid
    paid

Payment history must not be casually deleted.

Full refund infrastructure is outside V1.

## 24. Transaction Snapshot / Snapshot Transaksi

Historical transaction lines store snapshot values:
- description
- quantity
- unit price
- subtotal

Changing master service/spare-part data later must not alter historical invoices or transaction meaning.

## 25. Data Integrity / Integritas Data

Use both:
- application validation
- database constraints where practical

Important relationships must not permit cross-tenant references.

Deletion rules must preserve operational, inventory, and financial history.

## 26. Database Strategy / Strategi Database

Primary database: PostgreSQL.

Schema changes use Laravel migrations.

Default primary key: BIGINT auto-increment.

Do not globally adopt UUID/ULID without a real public/external identifier requirement.

## 27. Indexing Strategy / Strategi Index

Indexes follow real query patterns.

Common tenant-aware candidates:
- `(workshop_id, status)`
- `(workshop_id, created_at)`
- `(workshop_id, customer_id)`
- `(workshop_id, vehicle_id)`

Composite index order must reflect query patterns.

Avoid redundant indexes.

## 28. Tenant-Aware Uniqueness / Uniqueness Berdasarkan Tenant

Tenant-local uniqueness includes the workshop where appropriate.

Example:

    UNIQUE(workshop_id, normalized_plate_number)

The same plate may exist independently in different tenants.

## 29. Frontend Architecture / Arsitektur Frontend

Use:
- Blade
- Tailwind CSS
- Alpine.js
- Vite

Prefer server-rendered flows.

User-facing UI is Bahasa Indonesia.

Do not introduce an SPA without demonstrated need.

## 30. API Strategy / Strategi API

No separate public API is required initially.

Internal Laravel web routes are sufficient for V1.

If a future external API is required, authentication, authorization, tenant isolation, rate limiting, versioning, and public identifiers must be designed explicitly.

## 31. Background Jobs / Background Job

Prefer synchronous execution until background processing provides real value.

Potential future job use:
- email
- notifications
- heavy exports
- external integrations

Do not add queues or Redis merely because Laravel supports them.

## 32. Cache Strategy / Strategi Cache

Do not introduce caching prematurely.

Be especially careful caching:
- authorization
- tenant context
- inventory quantities
- financial totals

Correctness is more important than speculative optimization.

## 33. Security Boundary / Boundary Keamanan

Application security must address:
- CSRF
- XSS
- SQL injection
- IDOR/BOLA
- mass assignment
- open redirects
- path traversal
- insecure uploads
- brute force
- session security
- tenant isolation

Do not disable Laravel security middleware to simplify implementation.

## 34. Logging / Logging

Log meaningful failures and security-relevant events where appropriate.

Never log secrets.

Inventory and financial audit history belongs in explicit business records, not only logs.

## 35. Error Handling / Penanganan Error

Production errors must not expose:
- stack traces
- credentials
- secrets
- raw SQL
- sensitive filesystem paths

Return user-friendly errors while preserving useful server-side diagnostics.

## 36. Testing Architecture / Arsitektur Testing

Critical tests include:
- authentication
- authorization
- role boundaries
- tenant isolation
- IDOR/tampered IDs
- validation
- database constraints
- state transitions
- inventory integrity
- financial integrity

Tenant-isolation tests are mandatory.

## 37. Deployment Architecture / Arsitektur Deployment

Initial production may use a single secured VPS:

    Internet
       ↓
    HTTPS / Nginx
       ↓
    Laravel
       ↓
    PostgreSQL

Additional infrastructure should be introduced only when justified.

## 38. Infrastructure Security / Keamanan Infrastruktur

Production deployment must address:
- TLS/HTTPS
- firewall
- minimal exposed ports
- secure SSH
- database network exposure
- backups and restore testing
- OS updates
- filesystem permissions
- secrets
- `APP_DEBUG=false`

## 39. Scaling Strategy / Strategi Scaling

Scale based on evidence.

Likely progression:
1. optimize queries/indexes
2. improve application/server resources
3. introduce queues/cache only where needed
4. separate infrastructure only when actual load justifies it

Do not begin with distributed-system complexity.

## 40. Architecture Decision Rule / Aturan Keputusan Arsitektur

When choosing between two valid approaches, prefer the one that better preserves:
1. security
2. tenant isolation
3. data integrity
4. correctness
5. simplicity
6. maintainability

Do not optimize for architectural fashion.

## 41. Final Architecture Principle / Prinsip Arsitektur Akhir

**EN:** Keep BengkelOS secure, tenant-safe, understandable, and boring in the good sense. Add architectural complexity only when real requirements force it.

**ID:** Jaga BengkelOS tetap aman, terisolasi antar-tenant, mudah dipahami, dan sederhana. Tambahkan kompleksitas arsitektur hanya ketika kebutuhan nyata memang memaksanya.
