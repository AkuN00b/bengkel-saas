# BengkelOS — Agent Instructions / Instruksi Agent

> Version: 0.1  
> Project: BengkelOS  
> Repository: bengkel-saas  
> Product Type: Multi-tenant SaaS  
> Primary Market: Indonesian independent motorcycle workshops

## 1. Purpose / Tujuan Dokumen

**EN:** This file defines mandatory working rules for coding agents and developers working on BengkelOS. Read this file before modifying the repository.

**ID:** File ini mendefinisikan aturan kerja wajib untuk coding agent dan developer yang mengerjakan BengkelOS. Baca file ini sebelum mengubah repository.

## 2. Project Mission / Misi Project

**EN:** BengkelOS is a simple, mobile-friendly SaaS for small and medium independent motorcycle workshops in Indonesia. Its core promise is that an owner can understand the condition of the workshop from a phone without always being physically present.

**ID:** BengkelOS adalah SaaS sederhana dan mobile-friendly untuk bengkel motor independen kecil dan menengah di Indonesia. Janji utamanya adalah owner dapat memahami kondisi bengkel dari HP tanpa harus selalu berada secara fisik di bengkel.

Future modules may include customers, vehicles, work orders, mechanics, services, spare parts, inventory, invoices, payments, service history, dashboards, and reports.

**Never implement future modules unless explicitly requested.**

## 3. Product Principles / Prinsip Produk

**EN:**
- Simple before sophisticated.
- Operational usefulness before feature count.
- Mobile-friendly by default.
- Minimize clicks and training.
- A workshop employee should understand the essential workflow in about 10 minutes.
- Avoid ERP-like complexity.
- Solve real workshop problems.
- Assume modest devices and network conditions.

**ID:**
- Utamakan sederhana sebelum canggih.
- Utamakan kegunaan operasional sebelum jumlah fitur.
- Mobile-friendly secara default.
- Minimalkan klik dan kebutuhan training.
- Pegawai bengkel harus dapat memahami workflow utama dalam sekitar 10 menit.
- Hindari kompleksitas seperti ERP.
- Selesaikan masalah bengkel yang nyata.
- Asumsikan perangkat dan kondisi jaringan yang sederhana.

## 4. Technology Stack / Teknologi

Approved stack:

- PHP 8.4+
- Laravel
- Blade
- Tailwind CSS
- Alpine.js
- Vite
- PostgreSQL
- Composer
- Node.js / npm
- Pest or PHPUnit
- Git / GitHub

Do not introduce without explicit approval:

- React
- Vue
- Next.js
- separate frontend application
- microservices
- Redis merely because Laravel supports it
- Elasticsearch
- Kafka
- RabbitMQ
- mandatory Docker
- additional databases

## 5. Architecture / Arsitektur

**EN:** Use a modular monolith. Follow Laravel conventions unless there is a concrete reason not to. Keep controllers thin when business logic becomes non-trivial. Extract business logic only when doing so improves clarity, testing, or reuse.

Avoid premature:
- repository patterns
- generic interfaces
- generic abstractions
- excessive DTO layers
- event infrastructure
- microservice boundaries

**ID:** Gunakan modular monolith. Ikuti convention Laravel kecuali ada alasan konkret untuk tidak melakukannya. Jaga controller tetap tipis ketika business logic mulai kompleks. Ekstrak business logic hanya jika meningkatkan kejelasan, testing, atau reuse.

Hindari abstraction dan infrastructure prematur.

## 6. Multi-Tenancy / Multi-Tenant

`workshops` represent tenants.

Operational data must be isolated by workshop.

Never trust `workshop_id` supplied by the client as proof of tenant access.

Resolve the active workshop from authenticated membership and trusted server-side context.

Tenant-owned queries must be scoped to the active workshop.

**Critical rule: Tenant A must never access Tenant B data, including through manipulated IDs, URLs, requests, exports, or APIs.**

## 7. Authentication & Authorization / Autentikasi & Otorisasi

Initial roles:

- `super_admin`
- `owner`
- `cashier`
- `mechanic`

Authentication is not authorization.

Use server-side authorization through Laravel Policies, Gates, middleware, or equivalent appropriate mechanisms.

Sensitive operations must verify both:
1. user permission/role; and
2. tenant ownership.

Do not rely on hidden buttons or frontend checks for security.

## 8. Security Rules / Aturan Keamanan

### 8.1 Input & Request Security

Treat all user and external input as untrusted.

Use server-side validation. Prefer Laravel Form Requests when appropriate.

Frontend validation is for UX only.

Use allowlists for fields, enums, sorting, filtering, actions, and file types where appropriate.

Prevent mass-assignment vulnerabilities.

Never execute user-provided:
- commands
- code
- SQL
- filesystem paths
- templates

Use Eloquent or parameterized queries. Never build SQL by concatenating untrusted input.

### 8.2 Web Security

Preserve Laravel's security protections.

Protect against:
- CSRF
- XSS
- SQL injection
- IDOR / BOLA
- session fixation/hijacking
- open redirects
- insecure uploads
- path traversal
- brute force

Escape output by default. Raw HTML requires explicit justification.

Do not disable security middleware merely to make a feature work.

Use rate limiting where appropriate for login, password reset, registration, verification, public forms, and future APIs.

## 9. File Upload Security / Keamanan Upload File

When file uploads are introduced:

- validate MIME type
- validate allowed extensions
- enforce size limits
- generate server-controlled filenames
- prevent executable uploads
- prefer private storage for sensitive files
- authorize downloads
- never trust original filenames or paths

## 10. Secrets & Configuration / Secret & Konfigurasi

Never commit:
- `.env`
- API keys
- passwords
- database credentials
- tokens
- private keys
- production secrets

Do not log secrets.

Production error responses must not expose stack traces, credentials, SQL details, or sensitive filesystem paths.

## 11. Database Rules / Aturan Database

Primary database: PostgreSQL.

All schema changes use Laravel migrations.

Use:
- appropriate data types
- foreign keys
- constraints
- intentional nullable columns
- timestamps where useful
- indexes based on real query patterns

Important integrity rules should exist at the database layer when practical, in addition to application validation.

Once migrations have been shared or used in persistent environments, do not casually rewrite migration history. Create a new migration.

## 12. Primary Key Strategy / Strategi Primary Key

Default primary key:

`BIGINT` auto-increment.

Laravel migrations should normally use:

`$table->id();`

Security must never depend on IDs being difficult to guess.

UUID, ULID, or public tokens may be introduced only when a resource genuinely needs an external/public identifier, such as:
- public invoice links
- external API references
- webhooks
- QR-accessible public resources

## 13. Database Indexing / Index Database

Do not index every column.

Consider indexes for:
- frequent `WHERE` conditions
- joins
- ordering on large datasets
- lookup/search fields
- uniqueness
- tenant filtering

Common tenant-aware examples:

- `(workshop_id, status)`
- `(workshop_id, created_at)`
- `(workshop_id, customer_id)`
- `(workshop_id, vehicle_id)`

For queries such as:

`WHERE workshop_id = ? AND status = ? ORDER BY created_at DESC`

a composite index such as `(workshop_id, status, created_at)` may be appropriate when actual query behavior justifies it.

Column order matters.

Avoid redundant, duplicate, or unused indexes.

## 14. Tenant-Aware Constraints / Constraint Berdasarkan Tenant

Tenant-local uniqueness must include the tenant when appropriate.

Example:

`UNIQUE(workshop_id, normalized_plate_number)`

Do not use a global unique constraint when identical real-world values may legitimately exist in different tenants.

The same vehicle plate may therefore exist independently in different workshop tenants.

## 15. Query & Performance Rules / Aturan Query & Performa

- Avoid N+1 queries.
- Use eager loading intentionally.
- Paginate potentially large collections.
- Do not load unlimited records into memory.
- Optimize based on evidence.
- Do not introduce caching prematurely.
- Keep tenant filters explicit and reliable.

Correctness and tenant isolation take priority over micro-optimizations.

## 16. Laravel Coding Rules / Aturan Coding Laravel

Follow Laravel conventions.

Prefer:
- clear route naming
- Form Requests for meaningful validation
- Policies/Gates for authorization
- Eloquent relationships
- service/action classes only when business logic justifies them
- database transactions for multi-record operations requiring atomicity

Do not create abstraction layers merely because they might be useful someday.

## 17. Frontend & UX Rules / Aturan Frontend & UX

Use Blade + Tailwind CSS + Alpine.js.

User-facing UI should use Bahasa Indonesia unless explicitly decided otherwise.

Requirements:
- responsive
- mobile-friendly
- minimal clicks
- clear operational terminology
- no fake dashboard data
- no fake navigation for unimplemented modules

Do not introduce SPA complexity without demonstrated need.

## 18. Testing Rules / Aturan Testing

Every security-sensitive or business-critical feature must include relevant tests.

At minimum, consider:
- authentication
- authorization
- tenant isolation
- IDOR/tampered IDs
- validation
- role escalation
- database constraints
- state transitions
- financial/inventory integrity where applicable

Happy-path testing alone is insufficient for security-sensitive functionality.

Run the relevant test suite before declaring a task complete.

## 19. Dependency Rules / Aturan Dependency

Prefer Laravel/framework capabilities before adding third-party packages.

New dependencies should be:
- necessary
- maintained
- reputable
- compatible with the approved stack

Do not add packages solely to avoid writing a small amount of straightforward application code.

## 20. Logging & Audit / Logging & Audit

Log meaningful application failures and security-relevant events where appropriate.

Never log secrets or unnecessary sensitive personal data.

Inventory and financial history should use explicit business records/movements where required rather than relying only on application logs.

## 21. Infrastructure Security Boundary / Boundary Keamanan Infrastruktur

Application code must enforce application security.

Production deployment must separately address:
- HTTPS/TLS
- firewall
- minimal exposed ports
- secure SSH
- database not publicly exposed unless explicitly required and secured
- backups and restore testing
- OS/security updates
- filesystem permissions
- secret management
- `APP_DEBUG=false`

Do not assume application code alone secures the server/network layer.

## 22. Scope Control / Kontrol Scope

Do not implement these unless explicitly requested:

- payment gateway
- AI features
- full accounting
- paid WhatsApp API
- native Android/iOS applications
- loyalty points
- spare-part marketplace
- advanced multi-branch
- complex analytics/BI
- complex CRM
- IoT
- complex warehouse management
- payroll
- advanced mechanic commissions

## 23. Change Discipline / Disiplin Perubahan

Keep changes focused on the active task.

Do not refactor unrelated code without a concrete need.

Do not silently change:
- architecture
- tenant model
- security boundaries
- database strategy
- billing model
- major product behavior

Update documentation when an approved decision changes.

## 24. Agent Execution Workflow / Workflow Eksekusi Agent

### Before coding

1. Read the requested task.
2. Read relevant project documentation.
3. Inspect existing code and tests.
4. Understand tenant and authorization impact.
5. Identify schema/security implications.

### During coding

1. Implement only the requested scope.
2. Follow Laravel conventions.
3. Preserve tenant isolation.
4. Validate external input.
5. Enforce authorization server-side.
6. Add justified constraints/indexes.
7. Add relevant tests.

### After coding

Run relevant commands, normally including:

- `php artisan test` or `composer test`
- frontend build/tests when relevant
- migrations/checks when schema changed

Report:
- what changed
- important implementation decisions
- migrations added
- tests added/run
- commands and results
- known limitations
- anything requiring human review

Then stop.

## 25. Definition of Done / Definisi Selesai

A task is done only when applicable requirements are satisfied:

- requested functionality works
- Laravel conventions are respected
- validation exists
- authorization exists
- tenant isolation is preserved
- database constraints/indexes are appropriate
- relevant tests exist
- tests pass
- frontend build passes if relevant
- migrations work if relevant
- no secrets are committed
- unrelated code remains untouched
- documentation is updated when an approved decision changed

Happy path alone is not enough for security-sensitive features.

## 26. Code & Language Conventions / Konvensi Bahasa

Use English for:
- source code
- classes
- functions/methods
- variables
- database tables/columns
- routes/internal identifiers
- tests
- Git commit messages

Use Bahasa Indonesia for user-facing UI unless explicitly decided otherwise.

Main project documentation should be bilingual English + Indonesian.

Technical terms may remain in English when that is clearer.

## 27. Git Rules / Aturan Git

- Keep commits focused.
- Use English commit messages.
- Do not include unrelated changes.
- Do not force-push or rewrite shared history without explicit authorization.
- Never commit `.env`, secrets, credentials, unnecessary build artifacts, or local IDE configuration.
- Use a dedicated branch/review workflow for significant work when practical.

## 28. Decision Priority / Prioritas Keputusan

When tradeoffs occur, prioritize in this order:

1. Security
2. Tenant isolation
3. Data integrity
4. Correctness
5. Simplicity
6. Maintainability
7. UX
8. Performance
9. Architectural elegance

## 29. Uncertainty Rule / Aturan Ketidakpastian

If ambiguity significantly affects:
- architecture
- security
- database schema
- tenant boundaries
- billing
- destructive changes
- external integrations

do not invent a major decision.

Ask for clarification.

For small, reversible implementation details, choose the simplest solution consistent with approved project documentation.

## 30. Final Agent Rule / Aturan Akhir Agent

**EN:** Understand first. Implement only what was requested. Protect tenant data. Validate external input. Enforce authorization server-side. Test critical boundaries. Keep the solution simple. When the requested task is complete, STOP. Do not automatically continue to the next roadmap item.

**ID:** Pahami terlebih dahulu. Implementasikan hanya yang diminta. Lindungi data tenant. Validasi input eksternal. Terapkan authorization di server. Test boundary kritis. Jaga solusi tetap sederhana. Ketika task yang diminta selesai, BERHENTI. Jangan otomatis melanjutkan ke roadmap berikutnya.
