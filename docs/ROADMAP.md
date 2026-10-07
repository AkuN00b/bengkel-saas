# BengkelOS — Development Roadmap / Roadmap Pengembangan

> Version: 0.1  
> Architecture: Laravel Modular Monolith  
> Database: PostgreSQL  
> Strategy: Small Task → Test → Verify → Commit → STOP

## 1. Roadmap Purpose / Tujuan Roadmap

This document defines implementation order.

It is a planning document, not authorization to implement every listed task automatically.

Only implement the task explicitly requested.

## 2. Mandatory Execution Rule / Aturan Eksekusi Wajib

    READ TASK
        ↓
    INSPECT CURRENT CODE
        ↓
    IMPLEMENT ONLY TASK SCOPE
        ↓
    RUN TESTS
        ↓
    VERIFY RESULT
        ↓
    REPORT RESULT
        ↓
    COMMIT WHEN APPROPRIATE
        ↓
    STOP

When a task is complete:

> STOP. Do not automatically continue to the next roadmap task.

Ketika task selesai:

> BERHENTI. Jangan otomatis melanjutkan ke task berikutnya.

## 3. Development Phases / Fase Pengembangan

    PHASE 0 — Project Preparation
       ↓
    PHASE 1 — Foundation v0.1
       ↓
    PHASE 2 — Customer + Vehicle
       ↓
    PHASE 3 — Work Order + Mechanic
       ↓
    PHASE 4 — Services + Spare Parts + Inventory
       ↓
    PHASE 5 — Invoice + Payment
       ↓
    PHASE 6 — Owner Dashboard + Reports
       ↓
    ALPHA — Real Workshop Validation
       ↓
    BETA
       ↓
    PRODUCTION READINESS

## 4. P00 — Initialize Git Repository

Objective:
- initialize local Git repository if required
- use `main`
- connect approved GitHub remote
- create initial commit
- push initial history

Acceptance:
- valid commit exists
- remote `main` exists
- repository can be pulled normally
- working tree clean

STOP. Do not bootstrap Laravel automatically.

## 5. P01 — Add Project Documentation

Expected:

    AGENTS.md
    docs/
    ├── PRODUCT.md
    ├── ARCHITECTURE.md
    ├── DATABASE.md
    └── ROADMAP.md

Rules:
- preserve approved EN/ID documentation
- do not silently rewrite product/architecture decisions
- no secrets

STOP. Do not begin Laravel implementation automatically.

## 6. Phase 1 — Foundation v0.1

Goal:

    Laravel Bootstrap
    Authentication
    Workshop Tenant
    Membership + Roles
    Plans + Trial Subscription
    Active Workshop Context
    Tenant Isolation
    UI Shell
    Foundation Tests

Explicitly excluded from Foundation:
- customers
- vehicles
- Work Orders
- operational mechanic module
- services
- spare parts
- inventory
- invoices
- payments
- reports

## 7. F01 — Bootstrap Laravel

Scope:
- Laravel
- PHP 8.4+
- Blade
- Tailwind CSS
- Alpine.js
- Vite
- PostgreSQL
- Pest/PHPUnit according to project setup

Required:
- bootstrap application
- configure `.env.example` for PostgreSQL
- verify application starts
- verify database connection
- verify tests
- verify frontend build

Expected checks:

    php artisan --version
    php artisan migrate
    php artisan test
    npm run build

Do not add Redis, Docker, SPA frameworks, or product modules.

STOP.

## 8. F02 — Authentication

Implement:
- register
- login
- logout
- forgot password
- reset password
- protected authenticated routes

Security:
- Laravel password hashing
- CSRF
- server-side validation
- appropriate rate limiting
- no custom authentication cryptography

Tests:
- registration
- login/logout
- invalid credentials
- guest blocked
- password reset structure

STOP.

## 9. F03 — Workshop Tenant

Create workshop tenant model/schema and relationships.

Workshop is the operational security/data boundary.

Tests:
- create workshop
- migrations
- relationships
- factory where useful

STOP.

## 10. F04 — Membership + Roles

Create membership:

    workshop_user

Roles:
- owner
- cashier
- mechanic

Platform:
- super_admin

Constraint:

    UNIQUE(workshop_id, user_id)

Tests:
- valid membership
- duplicate rejected
- roles distinguishable
- unrelated user not a member

Do not build a complex permission UI.

STOP.

## 11. F05 — Plans + Subscription + Trial

Create:
- plans
- subscriptions

Concepts:
- trial
- starter
- pro

Potential states:
- trialing
- active
- expired
- cancelled

Approved owner provisioning:

    REGISTER OWNER
       ↓
    CREATE USER
       ↓
    CREATE WORKSHOP
       ↓
    OWNER MEMBERSHIP
       ↓
    TRIAL SUBSCRIPTION
       ↓
    DASHBOARD / ONBOARDING

Provision atomically.

Tests verify all required records are created and partial provisioning rolls back.

No payment gateway.

STOP.

## 12. F06 — Active Workshop + Tenant Isolation

Use:

    session.active_workshop_id

Flow:
- load memberships
- one workshop → activate automatically
- multiple workshops → choose
- verify membership
- save active workshop

Session value alone is not authorization.

Mandatory isolation tests:

    User A → Workshop A
    User B → Workshop B

Verify:
- A can access A
- B can access B
- A cannot access B
- B cannot access A
- tampered IDs fail

STOP.

## 13. F07 — Application UI Shell

Create responsive authenticated shell:
- navigation/sidebar
- top bar
- active workshop context
- user menu
- main content

UI language: Bahasa Indonesia.

Initial dashboard may show:
- welcome
- workshop name
- trial status/days
- setup checklist

Do not show fake business metrics or fake modules.

STOP.

## 14. F08 — Foundation Test & Hardening

Verify:
- auth
- owner provisioning
- roles
- authorization
- tenant isolation
- tampered IDs
- constraints
- clean migrations
- frontend build

Relevant commands:

    php artisan migrate:fresh --seed
    php artisan test
    npm run build

Foundation complete only when tests pass and tenant isolation is proven.

STOP. Sprint 1 requires explicit approval.

## 15. Sprint 1 — Customer + Vehicle

### C01 — Customer Management

Fields approximately:
- name
- phone optional
- notes optional

Requirements:
- tenant scoped
- mobile-friendly
- validated
- searchable
- duplicate names allowed

Tests:
- CRUD behavior
- validation
- authorization
- tenant isolation

STOP.

### C02 — Vehicle Management

Fields approximately:
- customer optional
- plate number
- normalized plate
- brand/model/year optional
- notes optional

Rule:

    UNIQUE(workshop_id, normalized_plate_number)

Tests:
- with/without customer
- normalization
- duplicate same tenant rejected
- same plate different tenant allowed
- cross-tenant customer denied

STOP.

### C03 — Customer + Vehicle UX

Core:

    Search Plate
       ↓
    Found?
    ├── yes → open vehicle
    └── no  → create vehicle

Optimize for mobile repeat-visit workflow.

STOP. Sprint 1 ends.

## 16. Sprint 2 — Work Order + Mechanic

### W01 — Work Order Foundation

Required:
- workshop
- vehicle

Optional:
- customer
- primary mechanic

States:
- draft
- open
- in_progress
- completed
- cancelled

Human-facing number unique per workshop.

Test tenant boundaries.

STOP.

### W02 — Work Order Lifecycle

Normal:

    draft → open → in_progress → completed

Validate transitions.

Cancellation follows approved business rules.

Do not permit arbitrary status mutation.

STOP.

### W03 — Mechanic Assignment

Assign one primary mechanic.

Mechanic must belong to active workshop and satisfy the approved role/access model.

No complex scheduling or multi-mechanic allocation.

STOP. Sprint 2 ends.

## 17. Sprint 3 — Service + Spare Part + Inventory

### I01 — Service Catalog

Tenant-scoped service master:
- name
- optional description
- default price
- active state

Money uses integer Rupiah.

STOP.

### I02 — Work Order Services

Store snapshots:
- description
- quantity
- unit price
- subtotal

Changing master price must not change historical WO values.

STOP.

### I03 — Spare Part Catalog

Fields approximately:
- SKU optional
- name
- selling price
- current stock
- minimum stock optional
- active state

Tenant scoped.

STOP.

### I04 — Inventory Movement

Types:
- restock
- work_order_usage
- work_order_return
- adjustment_in
- adjustment_out

Rules:
- preserve movement history
- stock mutation transactional
- no direct stock edits bypassing movements
- handle concurrency safely

Tests:
- restock
- usage
- return
- consistency
- tenant isolation
- invalid resulting stock
- concurrent usage

STOP.

### I05 — Work Order Spare Parts

    Add Part
       ↓
    PLANNED
       ↓
    no stock change
       ↓
    Confirm USED
       ↓
    Movement OUT
       ↓
    stock decreases

Return creates a new IN movement.

Tests prevent accidental double decrement.

Cancellation does not silently restore consumed stock.

STOP. Sprint 3 ends.

## 18. Sprint 4 — Invoice + Payment

### B01 — Invoice Draft

States:
- draft
- issued
- void

Snapshot invoice items.

Use integer Rupiah.

Test totals, snapshots, and tenant isolation.

STOP.

### B02 — Issue Invoice

Controlled:

    draft → issued

Issued financial snapshots are protected.

Material correction may use controlled void + reissue.

STOP.

### B03 — Payments

Relationship:

    Invoice 1 → 0..N Payments

Methods:
- cash
- bank_transfer
- other

Derived condition:
- unpaid
- partially_paid
- paid

No payment gateway or full refund infrastructure.

STOP. Sprint 4 ends.

## 19. Sprint 5 — Owner Dashboard + Reports

### D01 — Operational Dashboard

Use real data only.

Potential metrics:
- active Work Orders
- completed Work Orders
- daily revenue
- unpaid invoices
- low-stock items

Mobile-friendly and tenant scoped.

STOP.

### D02 — Basic Reports

Potential:
- daily transactions
- service revenue
- spare-part sales
- payment summary
- Work Order history

Only implement explicitly approved reports.

STOP. Sprint 5 ends.

## 20. Alpha Milestone

Core flow:

    Customer
      ↓
    Vehicle
      ↓
    Work Order
      ↓
    Mechanic
      ↓
    Service + Spare Parts
      ↓
    Inventory
      ↓
    Invoice
      ↓
    Payment
      ↓
    Service History
      ↓
    Owner Dashboard

Then focus on:

> Can a real workshop use this?

not:

> What else can we build?

## 21. Real Workshop Validation / Validasi Bengkel Nyata

Observe:
- customer/vehicle entry time
- WO creation
- mechanic assignment
- service entry
- spare-part usage
- invoice
- payment
- owner dashboard comprehension

Target:
- essential workflow understandable in about 10 minutes

## 22. Feedback Priority / Prioritas Feedback

    BLOCKER — core workflow cannot be completed
    HIGH    — possible but painful/error-prone
    MEDIUM  — useful improvement
    LOW     — nice-to-have

Fix blockers/high-impact operational problems before feature expansion.

## 23. Deferred Features / Fitur Ditunda

Not current roadmap unless explicitly promoted:
- payment gateway
- paid WhatsApp
- AI
- native mobile apps
- full accounting
- payroll
- advanced mechanic commissions
- loyalty
- spare-part marketplace
- advanced multi-branch
- advanced CRM/BI
- IoT
- complex warehouse management
- advanced refund infrastructure

Deferred means “not now.”

## 24. Production Readiness

Review:
- security
- tenant isolation
- authorization
- validation
- rate limiting
- logging
- database backups/restore
- constraints/indexes
- migration safety
- HTTPS
- firewall
- SSH
- DB exposure
- OS updates
- permissions
- secrets
- `APP_DEBUG=false`
- monitoring
- deployment/rollback

## 25. Definition of Done Per Task

Before completion:
- requested behavior works
- validation exists
- authorization exists
- tenant isolation preserved
- data integrity preserved
- relevant constraints/indexes exist
- tests exist and pass
- build passes if relevant
- migrations work if relevant
- unrelated code untouched
- docs updated if approved decisions changed
- no secrets committed

Then report and STOP.

## 26. Commit Strategy

Prefer focused English commits, for example:

    chore: bootstrap Laravel application
    feat: add workshop tenant model
    feat: add workshop memberships
    feat: add trial subscriptions
    feat: add active workshop context
    test: verify tenant isolation
    feat: add customer management
    feat: add vehicle management
    feat: add work order lifecycle
    feat: add inventory movements

## 27. Agent Task Interpretation

Instruction:

> Implement F03.

Means:

    read AGENTS.md
    read relevant docs
    inspect repository
    implement ONLY F03
    test
    report
    STOP

It does not authorize F04/F05/etc.

## 28. Architecture Change Rule / Aturan Perubahan Arsitektur

If a task conflicts with AGENTS/PRODUCT/ARCHITECTURE/DATABASE, do not silently invent a major change.

Ask before significant changes involving:
- security
- tenant model
- schema
- billing
- destructive operations
- external integrations

Small reversible details may use the simplest consistent solution.

## 29. Immediate Execution Plan / Rencana Eksekusi Terdekat

    P00  Initialize Git Repository
     ↓
    P01  Add Project Documentation
     ↓
    F01  Bootstrap Laravel
     ↓
    F02  Authentication
     ↓
    F03  Workshop Tenant
     ↓
    F04  Membership + Roles
     ↓
    F05  Plans + Subscription + Trial
     ↓
    F06  Active Workshop + Tenant Isolation
     ↓
    F07  Application UI Shell
     ↓
    F08  Foundation Test & Hardening
     ↓
    FOUNDATION v0.1 REVIEW
     ↓
    STOP

Sprint 1 starts only after explicit approval.

## 30. Final Roadmap Principle / Prinsip Roadmap Akhir

**EN:** Build one small reliable capability at a time. Test important boundaries before adding more features. The roadmap defines direction, not automatic execution authority. When the requested task is complete, STOP.

**ID:** Bangun satu capability kecil yang reliable dalam satu waktu. Test boundary penting sebelum menambah fitur. Roadmap menentukan arah, bukan izin eksekusi otomatis. Ketika task selesai, BERHENTI.
