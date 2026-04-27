# Hospital Insurance Billing System
**Stack:** Laravel 13 · PHP 8.3 · Blade + Tailwind CSS 4 · MySQL · Single hospital

---

## Project Overview

A web-based admin dashboard for managing inpatient insurance billing.
Patients are admitted under health insurance contracts. Daily services are
auto-charged. Invoices are itemized into 4 sections. A monthly A3 landscape
report covers all cases and their line items.

---

## Tech Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Blade + Tailwind CSS 4 |
| Build tool | Vite 8 |
| Database | MySQL |
| Auth & Roles | spatie/laravel-permission ^7.3 |
| Alerts | realrashid/sweet-alert ^7.3 |
| PDF (invoices + reports) | barryvdh/laravel-dompdf ^3.1 |
| Pagination | Laravel built-in Blade pagination |
| Code style | Laravel Pint |
| Testing | PHPUnit 12 |
| Logs / DX | Laravel Pail |

---

## Architecture Conventions

### Controllers
- **Thin controllers only.** No business logic inside controllers.
- All logic lives in `app/Services/`.
- Controllers handle: validate → call service → return view/redirect.

### Services
- One service per domain. Current services in `app/Services/`:
  `PatientService`, `AdmissionService`, `InvoiceService`, `ReportService`,
  `MedicationService`, `ServiceCatalogService`, `InsuranceCompanyService`, `UserService`.
- Services are injected via constructor DI with `readonly` property promotion.
- Lifecycle invariants belong in services (e.g. `InvoiceService` throws `LogicException`
  when a finalized invoice is mutated).

### Models
- Eloquent only — no raw SQL queries.
- Use model Observers for side effects (e.g. auto-add daily services on admission).
- Naming: PascalCase models, snake_case DB columns and tables.

### Views
- Blade templates in `resources/views/`.
- Layout: `layouts/app.blade.php` (authenticated), `layouts/guest.blade.php`.
- Partials in `resources/views/partials/`.
- Each module has its own subfolder: `patients/`, `admissions/`, `invoices/`, `reports/`, `catalog/`.

### Routes
- All routes in `routes/web.php` grouped by middleware and prefix.
- Route naming convention: `resource.action` e.g. `patients.index`, `invoices.show`.
- **Fixed-path routes (`/create`, `/print`, `/export`) MUST be registered before
  wildcard `{model}` routes** so they aren't swallowed by show wildcards.
  See existing comments in `routes/web.php` for examples.

### Enums
- `App\Enums\Role` and `App\Enums\Permission` are backed PHP enums.
- Always reference roles and permissions via these enums — never as bare strings.

---

## Database Schema

### Core Tables

```
patients
  id, name, national_id, dob, gender
  insurance_company_id (FK), policy_number
  created_at, updated_at

insurance_companies
  id, name, contact_info
  created_at, updated_at

admissions
  id, patient_id (FK)
  admission_date, discharge_date (nullable)
  room, ward
  status: active | discharged
  created_at, updated_at

medications
  id, name, unit, price
  type: local | imported   ← AUTO-DETERMINES invoice section
  created_at, updated_at

services
  id, name, price
  category: daily | lab | radiology
  created_at, updated_at

invoices
  id, admission_id (FK)
  invoice_date, status: draft | final
  total_amount
  created_at, updated_at

invoice_items
  id, invoice_id (FK)
  itemable_id, itemable_type  (polymorphic: Medication | Service)
  qty, unit_price, total
  section: local_med | imported_med | lab | radiology | daily  ← DENORMALIZED for fast reporting
  service_date (nullable, for daily services)
  created_at, updated_at
```

> The `users` table also has an `is_active` boolean (added by a follow-up
> migration) so accounts can be soft-disabled without deletion.

---

## Key Business Rules

### 1. Medication → Invoice Section (auto)
```
medication.type = 'local'    → invoice_item.section = 'local_med'
medication.type = 'imported' → invoice_item.section = 'imported_med'
```
Never ask the user to pick the section — derive it from the catalog.

### 2. Daily Services (auto-charge)
- When an `Admission` is created → `AdmissionObserver@created` fires.
- Observer (a) creates a draft `Invoice` for the admission and
  (b) inserts one `invoice_item` per day from `admission_date` to today
  for every `service.category = 'daily'`, then recalculates the invoice total.
- Daily items are written with `section = 'daily'` and a populated `service_date`.
- On each new day (scheduler or on discharge) → add that day's daily services.
- On discharge → finalize daily services up to `discharge_date`.

### 2b. Invoice lifecycle invariants
- Every admission has exactly one invoice (created by the observer).
- `Invoice::recalculateTotal()` is called after every item mutation.
- Finalized invoices are immutable: `InvoiceService::addItem` / `removeItem`
  throw `LogicException` if `status === 'final'`.

### 3. Invoice Structure (4 sections)
Invoice printout MUST show 4 separate breakdowns:
1. **Local Medications** — name, qty, unit price, total
2. **Imported Medications** — name, qty, unit price, total
3. **Lab Tests** — name, qty, unit price, total
4. **Radiology** — name, qty, unit price, total
Each section has its own subtotal. Grand total at the bottom.

### 4. Monthly A3 Report
- All admissions in a selected month.
- One row per admission with all line items.
- PDF output: A3 size, landscape orientation.
- Generated via `dompdf` with a dedicated Blade view `reports/monthly_a3.blade.php`.

---

## Roles & Permissions (spatie/laravel-permission)

| Role | Permissions |
|---|---|
| super_admin | everything including user management and role assignment |
| admin | manage catalog, view all reports, manage invoices (no delete) |
| cashier | view invoices, print, confirm payment |
| data_entry | register patients, add invoice line items |

Seed roles in `database/seeders/RolesAndPermissionsSeeder.php`.
Always use `@can` directives in Blade and Gate policies in controllers.

---

## Packages Usage Notes

### spatie/laravel-permission
- Use `@role` / `@can` in Blade.
- Apply `role:admin` middleware on route groups.
- Never hardcode role names — use constants or enums.

### realrashid/sweet-alert
- Use for all create/update/delete success and error feedback.
- Call `alert()->success('...')` in controller after redirect.
- Include `@include('sweetalert::alert')` in main layout.

### barryvdh/laravel-dompdf
- Invoice PDF: `resources/views/invoices/print.blade.php`
- Monthly report PDF: `resources/views/reports/monthly_a3.blade.php`
- Set paper size in controller: `$pdf->setPaper('a3', 'landscape')` for reports.
- Always inline CSS in PDF Blade views — dompdf does not support external stylesheets.

---

## File Structure

```
app/
  Enums/
    Role.php
    Permission.php
  Http/
    Controllers/
      DashboardController.php
      PatientController.php
      AdmissionController.php
      InvoiceController.php
      ReportController.php
      UserController.php
      Auth/
        LoginController.php
      Catalog/
        MedicationController.php
        ServiceController.php
        InsuranceCompanyController.php
  Services/
    PatientService.php
    AdmissionService.php
    InvoiceService.php
    ReportService.php
    MedicationService.php
    ServiceCatalogService.php
    InsuranceCompanyService.php
    UserService.php
  Observers/
    AdmissionObserver.php
  Models/
    Patient.php
    Admission.php
    Invoice.php
    InvoiceItem.php
    Medication.php
    Service.php
    InsuranceCompany.php
    User.php

resources/views/
  layouts/
    app.blade.php
    guest.blade.php
  partials/
  auth/
  dashboard/
  patients/
  admissions/
  invoices/
    print.blade.php       ← PDF invoice view
  reports/
    monthly_a3.blade.php  ← A3 landscape PDF view
  catalog/
  users/

lang/
  ar/
  ar.json                 ← Arabic localization

database/
  migrations/
  seeders/
    RolesAndPermissionsSeeder.php
    AdminUserSeeder.php
```

---

## Commands to Run

```bash
# After any change to models/migrations
php artisan migrate

# Before committing
php artisan test

# Fresh staging reset
php artisan migrate:fresh --seed

# All-in-one dev environment (server + queue + logs + Vite)
composer dev

# Frontend build
npm run build

# Register observers (in AppServiceProvider)
Admission::observe(AdmissionObserver::class);
```

---

## What NOT to Do

- No raw SQL — Eloquent only.
- No logic in Blade views — controllers/services only.
- No hardcoded role/permission names as strings in logic — use the
  `App\Enums\Role` and `App\Enums\Permission` enums.
- Do not use `spatie/laravel-translatable` — not needed.
- Do not use `appstract/laravel-options` — not needed.
- Do not use `maatwebsite/excel` — not currently installed; do not add bulk
  Excel import without first agreeing on scope.
- Do not put CSS in external files for PDF Blade views.
- Do not use JavaScript-heavy solutions — this is a Blade/server-rendered app.
- Do not register a wildcard `{model}` route before fixed-path siblings
  (`/create`, `/print`, `/export`) — it will shadow them.
