# Hospital Insurance Billing System
**Stack:** Laravel 11 · Blade · MySQL · Single hospital

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
| Backend | Laravel 11 |
| Frontend | Blade + Bootstrap 5 (or Tailwind — TBD) |
| Database | MySQL |
| Auth & Roles | spatie/laravel-permission |
| Alerts | realrashid/sweet-alert |
| PDF (invoices + reports) | barryvdh/laravel-dompdf |
| Excel import (catalog) | maatwebsite/excel |
| Pagination | Laravel built-in Blade pagination |

---

## Architecture Conventions

### Controllers
- **Thin controllers only.** No business logic inside controllers.
- All logic lives in `app/Services/`.
- Controllers handle: validate → call service → return view/redirect.

### Services
- One service per domain: `PatientService`, `AdmissionService`, `InvoiceService`, `ReportService`.
- Services are injected via constructor DI.

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
  section: local_med | imported_med | lab | radiology  ← DENORMALIZED for fast reporting
  service_date (nullable, for daily services)
  created_at, updated_at
```

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
- Observer adds one `invoice_item` per day from `admission_date` to today for every `service.category = 'daily'`.
- On each new day (scheduler or on discharge) → add that day's daily services.
- On discharge → finalize daily services up to `discharge_date`.

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

### maatwebsite/excel
- Use for bulk import of medications and services catalog.
- Create import classes in `app/Imports/`.

---

## File Structure

```
app/
  Http/
    Controllers/
      PatientController.php
      AdmissionController.php
      InvoiceController.php
      ReportController.php
      Catalog/
        MedicationController.php
        ServiceController.php
        InsuranceCompanyController.php
  Services/
    PatientService.php
    AdmissionService.php
    InvoiceService.php
    ReportService.php
  Observers/
    AdmissionObserver.php
  Imports/
    MedicationsImport.php
    ServicesImport.php
  Models/
    Patient.php
    Admission.php
    Invoice.php
    InvoiceItem.php
    Medication.php
    Service.php
    InsuranceCompany.php

resources/views/
  layouts/
    app.blade.php
    guest.blade.php
  partials/
    sidebar.blade.php
    alerts.blade.php
  patients/
  admissions/
  invoices/
    print.blade.php       ← PDF invoice view
  reports/
    monthly_a3.blade.php  ← A3 landscape PDF view
  catalog/

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

# Register observers (in AppServiceProvider)
Admission::observe(AdmissionObserver::class);
```

---

## What NOT to Do

- No raw SQL — Eloquent only.
- No logic in Blade views — controllers/services only.
- No hardcoded role names as strings in logic — use constants.
- Do not use `spatie/laravel-translatable` — not needed.
- Do not use `appstract/laravel-options` — not needed.
- Do not put CSS in external files for PDF Blade views.
- Do not use JavaScript-heavy solutions — this is a Blade/server-rendered app.
