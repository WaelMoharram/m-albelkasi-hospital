# نظام فوترة التأمين الصحي للمستشفى

## نظرة عامة على المشروع

نظام إدارة (Admin Dashboard) ويب لإدارة **فوترة المرضى المنومين تحت تغطية شركات التأمين الصحي** في مستشفى واحد. النظام يقوم بتسجيل المرضى، إدارة الإقامة (Admissions)، احتساب الخدمات اليومية تلقائياً، وإصدار فواتير مفصّلة وتقارير شهرية للطباعة.

---

## التقنيات المستخدمة (Stack)

| الطبقة | التقنية |
|---|---|
| Backend | Laravel 11 |
| Frontend | Blade + Bootstrap 5 |
| Database | MySQL |
| الصلاحيات | spatie/laravel-permission |
| التنبيهات | realrashid/sweet-alert |
| PDF | barryvdh/laravel-dompdf |
| استيراد Excel | maatwebsite/excel |
| Pagination | Laravel built-in |

---

## الخصائص الرئيسية (Features)

### 1. إدارة المرضى (Patients)
- تسجيل بيانات المريض (الاسم، الرقم القومي، تاريخ الميلاد، النوع).
- ربط المريض بشركة التأمين ورقم البوليصة.

### 2. إدارة شركات التأمين (Insurance Companies)
- إضافة وتعديل شركات التأمين وبيانات التواصل.

### 3. إدارة الإقامة / التنويم (Admissions)
- تسجيل دخول المريض (تاريخ الدخول، الغرفة، القسم).
- متابعة الحالة: **نشطة (active)** أو **خرج (discharged)**.
- **خاصية مميزة:** عند إنشاء إقامة جديدة، يقوم `AdmissionObserver` تلقائياً بإضافة الخدمات اليومية (Daily Services) لكل يوم منذ الدخول.
- عند الخروج، تُحدّث الخدمات اليومية حتى تاريخ الخروج.

### 4. كتالوج الخدمات والأدوية (Catalog)
- **الأدوية:** اسم، وحدة، سعر، نوع (محلي / مستورد).
- **الخدمات:** اسم، سعر، تصنيف (يومي / مختبر / أشعة).
- استيراد جماعي للكتالوج عبر ملفات Excel.

### 5. الفواتير (Invoices) — أهم خاصية
الفاتورة تُقسّم تلقائياً إلى **4 أقسام منفصلة** مع subtotal لكل قسم وإجمالي عام:
1. **أدوية محلية** (Local Medications)
2. **أدوية مستوردة** (Imported Medications)
3. **تحاليل المختبر** (Lab Tests)
4. **الأشعة** (Radiology)

> القسم يُحدَّد آلياً من نوع الدواء/الخدمة في الكتالوج — المستخدم لا يختاره يدوياً.

- حالات الفاتورة: مسودة (draft) / نهائية (final).
- طباعة الفاتورة كـ PDF.

### 6. التقارير الشهرية (Monthly Reports)
- تقرير شهري شامل لكل حالات التنويم وبنود الفواتير.
- مخرجات **PDF بحجم A3 عرضي (Landscape)** للطباعة.
- صف لكل إقامة مع كل بنودها.

### 7. إدارة المستخدمين والصلاحيات (Users & Roles)
أربع أدوار:

| الدور | الصلاحيات |
|---|---|
| `super_admin` | كل شيء + إدارة المستخدمين والأدوار |
| `admin` | إدارة الكتالوج والفواتير ومشاهدة التقارير (بدون حذف) |
| `cashier` | عرض الفواتير، الطباعة، تأكيد الدفع |
| `data_entry` | تسجيل المرضى وإضافة بنود الفاتورة |

### 8. لوحة التحكم (Dashboard)
- صفحة رئيسية بمؤشرات سريعة عن الحالة العامة.

### 9. الترجمة / التعريب (Localization)
- النظام يدعم اللغة العربية (واجهة مترجمة).

---

## بنية قاعدة البيانات (Core Tables)

- `patients` — بيانات المرضى وربطهم بشركة التأمين.
- `insurance_companies` — شركات التأمين.
- `admissions` — حالات التنويم.
- `medications` — كتالوج الأدوية (محلي/مستورد).
- `services` — كتالوج الخدمات (يومي/مختبر/أشعة).
- `invoices` — رؤوس الفواتير.
- `invoice_items` — بنود الفاتورة (polymorphic مع `Medication` أو `Service`)، ويحتوي حقل `section` denormalized لتسريع التقارير.

---

## مبادئ معمارية مهمة

- **Thin Controllers:** كل منطق العمل (Business Logic) في طبقة `app/Services/`.
- **Eloquent فقط** — لا توجد استعلامات SQL خام.
- **Observers** للتعامل مع التأثيرات الجانبية (مثل إضافة الخدمات اليومية تلقائياً).
- **Server-rendered** عبر Blade — بدون SPA أو JavaScript ثقيل.
- فصل الـ Controllers حسب الموديول، وكتالوج الإدارة تحت `Catalog/`.

---

## هيكل المجلدات

```
app/
  Http/Controllers/        Patient, Admission, Invoice, Report, User, Dashboard, Catalog/*
  Services/                PatientService, AdmissionService, InvoiceService, ReportService, ...
  Observers/               AdmissionObserver
  Imports/                 MedicationsImport, ServicesImport
  Models/                  Patient, Admission, Invoice, InvoiceItem, Medication, Service, InsuranceCompany, User

resources/views/
  layouts/                 app, guest
  partials/                sidebar, alerts
  patients/  admissions/  invoices/  reports/  catalog/  users/  dashboard/  auth/

database/
  migrations/
  seeders/                 RolesAndPermissionsSeeder, AdminUserSeeder
```

---

## أوامر التشغيل الأساسية

```bash
# بعد أي تعديل في الموديلات أو المايجريشن
php artisan migrate

# تشغيل الاختبارات قبل الـ commit
php artisan test

# إعادة تهيئة بيئة الستيجينج
php artisan migrate:fresh --seed
```
