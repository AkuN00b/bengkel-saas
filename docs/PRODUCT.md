# BengkelOS — Product Definition / Definisi Produk

> Version: 0.1  
> Product: BengkelOS  
> Product Type: Multi-tenant SaaS  
> Primary Market: Bengkel motor independen kecil–menengah di Indonesia

## 1. Product Overview / Gambaran Produk

**EN:** BengkelOS is a simple, mobile-friendly SaaS for independent motorcycle workshops. It helps owners and staff manage workshop operations without turning the product into a complicated ERP.

**ID:** BengkelOS adalah SaaS sederhana dan mobile-friendly untuk bengkel motor independen. Produk membantu owner dan pegawai mengelola operasional bengkel tanpa berubah menjadi ERP yang rumit.

Core promise / Janji utama:

> Owner can understand workshop conditions from a phone without always being physically present.  
> Owner bisa mengetahui kondisi bengkel dari HP tanpa harus selalu berada di bengkel.

## 2. Target Market / Target Pasar

Initial target:
- independent motorcycle workshops
- small to medium operations
- owner + cashier + several mechanics
- workshops that also sell spare parts

Not the initial target:
- large official dealer networks
- enterprise workshop chains
- businesses requiring full ERP/accounting suites

## 3. Primary Users / Pengguna Utama

- `owner` — workshop owner
- `cashier` — front desk/cashier
- `mechanic` — mechanic with system access when needed
- `super_admin` — BengkelOS platform operator

## 4. Core Problems / Masalah Utama

BengkelOS focuses on:
- manual service records
- difficult spare-part stock tracking
- slow or unreliable reports
- unclear workshop status when the owner is away
- fragmented customer and vehicle history
- work-order, service, inventory, invoice, and payment data that are not connected

## 5. Product Value Proposition / Value Proposition

**EN:** Make daily workshop operations visible, traceable, and easy to operate from ordinary devices.

**ID:** Membuat operasional bengkel sehari-hari mudah dilihat, ditelusuri, dan dijalankan dari perangkat biasa.

Product principle:

> A workshop employee should understand the essential workflow in about 10 minutes.  
> Pegawai bengkel harus memahami workflow utama dalam sekitar 10 menit.

## 6. Core Workshop Flow / Alur Utama Bengkel

    MOTORCYCLE ARRIVES
            ↓
    CUSTOMER + VEHICLE IDENTIFIED
            ↓
         WORK ORDER
            ↓
         COMPLAINT
            ↓
         MECHANIC
            ↓
          SERVICES
            ↓
        SPARE PARTS
            ↓
         INVENTORY
            ↓
         COMPLETED
            ↓
          INVOICE
            ↓
          PAYMENT
            ↓
      SERVICE HISTORY

Customer identification is optional when the real workflow does not know the customer yet. A service Work Order requires a vehicle but may have no recorded customer.

## 7. Customer & Vehicle / Customer & Kendaraan

Approved relationship:

    Customer 1 → 0..N Vehicles

Rules:
- a vehicle may exist without a customer
- do not create fake “Customer Umum” records merely to satisfy the database
- the vehicle is the main anchor for service history
- a vehicle may later be associated with a different current customer
- old Work Orders preserve their historical transaction context
- ownership-history tables are not required in V1

## 8. Work Order / Work Order

For a service Work Order:
- `workshop_id` required
- `vehicle_id` required
- `customer_id` optional

Approved states:

    draft
      ↓
    open
      ↓
    in_progress
      ↓
    completed

Additional state:

    cancelled

Work status and payment status are separate concepts.

`completed` does not mean `paid`.

## 9. Mechanic / Mekanik

V1 should remain operationally simple.

A Work Order initially supports one primary assigned mechanic. Complex scheduling, multiple-mechanic allocation, payroll, and advanced commission systems are not part of the initial scope.

Mechanic account requirements may evolve based on real workshop usage; do not add unnecessary login complexity without validation.

## 10. Services / Jasa

Work Orders may contain one or more service lines.

Transaction lines must preserve snapshots such as:
- description
- quantity
- unit price
- subtotal

Changing a master service price later must not change historical transactions.

## 11. Spare Parts & Inventory / Sparepart & Inventory

Adding a spare part to a Work Order does not immediately reduce stock.

Approved flow:

    PART PLANNED
         ↓
    CONFIRM USED
         ↓
    INVENTORY MOVEMENT OUT
         ↓
    STOCK DECREASES

If a used part is physically returned to usable stock:

    EXPLICIT RETURN
         ↓
    INVENTORY MOVEMENT IN

Do not automatically return stock merely because a Work Order is cancelled.

Inventory must retain movement history. Historical movements must not be silently deleted to “fix” stock.

## 12. Invoice / Invoice

An invoice may exist as a draft while work is in progress.

Document lifecycle:

    draft
      ↓
    issued

Additional state:

    void

Rules:
- draft invoice may be edited
- issued invoice is an official document and must not be silently rewritten
- material corrections to an issued unpaid invoice should use a controlled void/reissue approach in V1
- invoice items preserve transaction snapshots

## 13. Payments / Pembayaran

Invoice and payment are separate.

Relationship:

    Invoice 1 → 0..N Payments

Initial payment methods:
- `cash`
- `bank_transfer`
- `other`

Payment condition:

    unpaid
    partially_paid
    paid

Example:

    Invoice Rp500.000
    Payment #1 Rp200.000 cash
    Payment #2 Rp300.000 transfer
    → paid

Do not model payment as a Work Order status.

Payment history must not be casually hard-deleted.

Full refund infrastructure is outside V1.

## 14. Owner Dashboard / Dashboard Owner

The dashboard should help the owner understand the workshop from a phone.

Potential real-data metrics after the relevant modules exist:
- active Work Orders
- completed Work Orders
- daily revenue
- unpaid invoices
- low-stock spare parts

Never show fake operational metrics.

## 15. Multi-Tenant Product Model / Model Multi-Tenant

One BengkelOS application serves multiple workshops.

Workshop data must remain isolated.

A user may eventually belong to multiple workshops, so the product must not assume one permanent workshop per user.

## 16. Product Simplicity / Kesederhanaan Produk

Prefer:
- minimal clicks
- clear Indonesian operational language
- responsive web UI
- simple forms
- fast plate/customer lookup
- obvious next actions

Avoid:
- ERP-like menus
- unnecessary configuration
- advanced permission builders in V1
- workflows requiring extensive training

## 17. Mobile-First Operational Usage / Penggunaan Mobile-First

V1 is a responsive web application.

Native Android and iOS applications are not required initially.

Important workflows must remain comfortable on a phone.

## 18. Foundation Phase / Fase Foundation

Before operational modules, build:
- Laravel bootstrap
- authentication
- workshop tenant
- membership and roles
- plans/subscriptions/trial
- active workshop context
- tenant isolation
- responsive application shell
- foundation tests

Foundation does not include operational workshop modules.

## 19. MVP Modules / Modul MVP

Approved implementation direction:

1. Foundation
2. Customer + Vehicle
3. Work Order + Mechanic
4. Services + Spare Parts + Inventory
5. Invoice + Payment
6. Owner Dashboard + Basic Reports
7. Alpha validation with real workshop workflows

## 20. MVP Exclusions / Di Luar MVP

Do not implement unless explicitly promoted:
- payment gateway
- AI
- full accounting
- paid WhatsApp API
- native Android/iOS apps
- loyalty points
- spare-part marketplace
- complex multi-branch
- advanced analytics/BI
- complex CRM
- IoT
- complex warehouse management
- payroll
- advanced mechanic commissions
- advanced refund infrastructure

## 21. Subscription Hypothesis / Hipotesis Subscription

Initial hypothesis only:

- Trial: 14 days, free
- Starter: around Rp79.000/month
- Pro: around Rp149.000/month
- Business: future multi-branch tier

Pricing must be validated with real workshops before being treated as final.

## 22. Product Success Criteria / Kriteria Sukses

The MVP succeeds when:
- staff can complete the core workflow with little assistance
- owner can understand important workshop conditions from a phone
- customer/vehicle history is useful on repeat visits
- stock movement is trustworthy
- invoices and payments are understandable
- tenant data remains isolated
- the product feels simpler than manual/ERP-heavy alternatives

## 23. Product Decision Filter / Filter Keputusan Produk

Before adding a feature, ask:
1. Does this solve a real workshop problem?
2. Does it strengthen the core workflow?
3. Can staff understand it quickly?
4. Does it add unnecessary operational complexity?
5. Has real usage validated the need?

“Common in other SaaS products” is not enough reason to build it.

## 24. Product North Star / North Star Produk

**EN:** The owner can control and understand the important parts of the workshop from a phone while staff operate the system with minimal training.

**ID:** Owner dapat mengontrol dan memahami bagian penting bengkel dari HP sementara pegawai menjalankan sistem dengan training minimal.
