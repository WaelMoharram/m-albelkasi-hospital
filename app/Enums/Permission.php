<?php

namespace App\Enums;

enum Permission: string
{
    // Roles & user management
    case ManageRoles = 'manage_roles';
    case ManageUsers = 'manage_users';
    case AssignRoles = 'assign_roles';

    // Catalog — umbrella (controls whether the Catalog menu is shown at all)
    case ManageCatalog = 'manage_catalog';

    // Catalog — medications
    case ViewMedications = 'view_medications';
    case ManageMedications = 'manage_medications';
    case ExportMedications = 'export_medications';

    // Catalog — services
    case ViewServices = 'view_services';
    case ManageServices = 'manage_services';
    case ExportServices = 'export_services';

    // Catalog — insurance companies
    case ViewInsuranceCompanies = 'view_insurance_companies';
    case ManageInsuranceCompanies = 'manage_insurance_companies';

    // Catalog — invoice categories
    case ViewInvoiceCategories = 'view_invoice_categories';
    case ManageInvoiceCategories = 'manage_invoice_categories';

    // Catalog — units
    case ViewUnits = 'view_units';
    case ManageUnits = 'manage_units';

    // Catalog — wards & rooms
    case ViewWards = 'view_wards';
    case ManageWards = 'manage_wards';

    // Catalog — health check (duplicate codes)
    case ViewCatalogHealth = 'view_catalog_health';

    // Patients
    case RegisterPatients = 'register_patients';
    case ViewPatients = 'view_patients';
    case EditPatients = 'edit_patients';
    case DeletePatients = 'delete_patients';

    // Admissions
    case ManageAdmissions = 'manage_admissions';
    case ViewAdmissions = 'view_admissions';
    case DeleteAdmissions = 'delete_admissions';

    // Invoices — actions
    case ViewInvoices = 'view_invoices';
    case CreateInvoices = 'create_invoices';
    case EditInvoices = 'edit_invoices';
    case DeleteInvoices = 'delete_invoices';
    case PrintInvoices = 'print_invoices';
    case ConfirmPayment = 'confirm_payment';
    case AddInvoiceItems = 'add_invoice_items';
    case FinalizeInvoices = 'finalize_invoices';

    // Invoices — field-level visibility on the invoice show page
    case ViewInvoicePatientIdNumber = 'view_invoice_patient_id_number';
    case ViewInvoiceDiagnosis = 'view_invoice_diagnosis';
    case ViewInvoiceReferralNumber = 'view_invoice_referral_number';
    case ViewInvoiceInsurance = 'view_invoice_insurance';
    case ViewInvoiceRoomWard = 'view_invoice_room_ward';
    case ViewInvoiceAge = 'view_invoice_age';
    case ViewInvoiceDuration = 'view_invoice_duration';
    case ViewInvoiceRemainingDays = 'view_invoice_remaining_days';
    case ViewInvoiceCostPerDay = 'view_invoice_cost_per_day';
    case ViewInvoicePatientType = 'view_invoice_patient_type';
    case ViewInvoiceSectionDaily = 'view_invoice_section_daily';
    case ViewInvoiceSectionLocalMed = 'view_invoice_section_local_med';
    case ViewInvoiceSectionImportedMed = 'view_invoice_section_imported_med';
    case ViewInvoiceSectionSupplies = 'view_invoice_section_supplies';
    case ViewInvoiceSectionLab = 'view_invoice_section_lab';
    case ViewInvoiceSectionOther = 'view_invoice_section_other';
    case ViewInvoiceDiscounts = 'view_invoice_discounts';
    case ViewInvoiceGrandTotal = 'view_invoice_grand_total';
    case ViewInvoiceHioExport = 'view_invoice_hio_export';

    // Reports
    case ViewReports = 'view_reports';
    case ViewReportClaim = 'view_report_claim';
    case ViewReportPatientList = 'view_report_patient_list';
    case ViewReportSummary = 'view_report_summary';
    case ViewReportPerformance = 'view_report_performance';
    case ExportMonthlyReport = 'export_monthly_report';

    // Settings
    case ViewSettings = 'view_settings';
    case ManageSettings = 'manage_settings';

    /**
     * Arabic label shown on the roles management screen.
     */
    public function label(): string
    {
        return match ($this) {
            self::ManageRoles => 'إدارة الأدوار والصلاحيات',
            self::ManageUsers => 'إدارة المستخدمين',
            self::AssignRoles => 'تعيين الأدوار للمستخدمين',

            self::ManageCatalog => 'الوصول لقائمة الكتالوج',

            self::ViewMedications => 'عرض الأدوية',
            self::ManageMedications => 'إضافة/تعديل/حذف الأدوية',
            self::ExportMedications => 'تصدير الأدوية إلى Excel',

            self::ViewServices => 'عرض الخدمات',
            self::ManageServices => 'إضافة/تعديل/حذف الخدمات',
            self::ExportServices => 'تصدير الخدمات إلى Excel',

            self::ViewInsuranceCompanies => 'عرض شركات التأمين',
            self::ManageInsuranceCompanies => 'إضافة/تعديل/حذف شركات التأمين',

            self::ViewInvoiceCategories => 'عرض أقسام الفاتورة',
            self::ManageInvoiceCategories => 'إضافة/تعديل/حذف أقسام الفاتورة',

            self::ViewUnits => 'عرض الوحدات',
            self::ManageUnits => 'إضافة/تعديل/حذف الوحدات',

            self::ViewWards => 'عرض العنابر والغرف',
            self::ManageWards => 'إضافة/تعديل/حذف العنابر والغرف',

            self::ViewCatalogHealth => 'عرض تقرير الأكواد المكررة',

            self::RegisterPatients => 'تسجيل مريض جديد',
            self::ViewPatients => 'عرض المرضى',
            self::EditPatients => 'تعديل بيانات المرضى',
            self::DeletePatients => 'حذف المرضى',

            self::ManageAdmissions => 'إضافة/تعديل حالات الدخول',
            self::ViewAdmissions => 'عرض حالات الدخول',
            self::DeleteAdmissions => 'حذف حالات الدخول',

            self::ViewInvoices => 'عرض الفواتير',
            self::CreateInvoices => 'إنشاء فواتير',
            self::EditInvoices => 'تعديل بنود الفاتورة',
            self::DeleteInvoices => 'حذف الفواتير',
            self::PrintInvoices => 'طباعة الفواتير',
            self::ConfirmPayment => 'تأكيد الدفع',
            self::AddInvoiceItems => 'إضافة/حذف بنود الفاتورة',
            self::FinalizeInvoices => 'اعتماد (تصفية) الفاتورة',

            self::ViewInvoicePatientIdNumber => 'عرض الرقم القومي للمريض',
            self::ViewInvoiceDiagnosis => 'عرض التشخيص',
            self::ViewInvoiceReferralNumber => 'عرض رقم التحويل',
            self::ViewInvoiceInsurance => 'عرض اسم شركة التأمين',
            self::ViewInvoiceRoomWard => 'عرض الغرفة والعنبر',
            self::ViewInvoiceAge => 'عرض عمر المريض',
            self::ViewInvoiceDuration => 'عرض مدة الإقامة',
            self::ViewInvoiceRemainingDays => 'عرض الأيام المتاحة المتبقية',
            self::ViewInvoiceCostPerDay => 'عرض تكلفة اليوم',
            self::ViewInvoicePatientType => 'عرض فئة المريض',
            self::ViewInvoiceSectionDaily => 'عرض تبويب الفاتورة (الخدمات اليومية)',
            self::ViewInvoiceSectionLocalMed => 'عرض قسم الأدوية المحلية',
            self::ViewInvoiceSectionImportedMed => 'عرض قسم الأدوية المستوردة',
            self::ViewInvoiceSectionSupplies => 'عرض قسم المستلزمات',
            self::ViewInvoiceSectionLab => 'عرض قسم التحاليل',
            self::ViewInvoiceSectionOther => 'عرض قسم الأصناف الأخرى',
            self::ViewInvoiceDiscounts => 'عرض الخصومات',
            self::ViewInvoiceGrandTotal => 'عرض الإجمالي الكلي',
            self::ViewInvoiceHioExport => 'عرض بيانات تصدير هيئة التأمين الصحي (HIO)',

            self::ViewReports => 'الوصول لقائمة التقارير',
            self::ViewReportClaim => 'عرض/طباعة كشف المطالبات',
            self::ViewReportPatientList => 'عرض/طباعة قائمة المرضى',
            self::ViewReportSummary => 'عرض/طباعة الملخص',
            self::ViewReportPerformance => 'عرض/طباعة مؤشرات الأداء',
            self::ExportMonthlyReport => 'تصدير التقرير الشهري (A3)',

            self::ViewSettings => 'عرض الإعدادات',
            self::ManageSettings => 'تعديل الإعدادات',
        };
    }

    /**
     * Module group key — used to cluster permissions on the roles form.
     */
    public function group(): string
    {
        return match (true) {
            in_array($this, [self::ManageRoles, self::ManageUsers, self::AssignRoles]) => 'users_roles',
            in_array($this, [
                self::ManageCatalog,
                self::ViewMedications, self::ManageMedications, self::ExportMedications,
                self::ViewServices, self::ManageServices, self::ExportServices,
                self::ViewInsuranceCompanies, self::ManageInsuranceCompanies,
                self::ViewInvoiceCategories, self::ManageInvoiceCategories,
                self::ViewUnits, self::ManageUnits,
                self::ViewWards, self::ManageWards,
                self::ViewCatalogHealth,
            ]) => 'catalog',
            in_array($this, [self::RegisterPatients, self::ViewPatients, self::EditPatients, self::DeletePatients]) => 'patients',
            in_array($this, [self::ManageAdmissions, self::ViewAdmissions, self::DeleteAdmissions]) => 'admissions',
            in_array($this, [
                self::ViewInvoices, self::CreateInvoices, self::EditInvoices, self::DeleteInvoices,
                self::PrintInvoices, self::ConfirmPayment, self::AddInvoiceItems, self::FinalizeInvoices,
            ]) => 'invoices_actions',
            in_array($this, self::invoiceFieldPermissions()) => 'invoices_fields',
            in_array($this, [
                self::ViewReports, self::ViewReportClaim, self::ViewReportPatientList,
                self::ViewReportSummary, self::ViewReportPerformance, self::ExportMonthlyReport,
            ]) => 'reports',
            default => 'settings',
        };
    }

    public static function groupLabels(): array
    {
        return [
            'users_roles' => 'المستخدمون والأدوار',
            'catalog' => 'الكتالوج',
            'patients' => 'المرضى',
            'admissions' => 'حالات الدخول',
            'invoices_actions' => 'الفواتير — إجراءات',
            'invoices_fields' => 'الفواتير — عرض البيانات',
            'reports' => 'التقارير',
            'settings' => 'الإعدادات',
        ];
    }

    /** Permission cases grouped by module, in enum declaration order. */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $groups[$case->group()][] = $case;
        }

        return $groups;
    }

    /**
     * Field-level invoice view permissions — granted in bulk to any role
     * that could already see the full invoice show page before these
     * existed, so introducing them never hides data a role could see.
     */
    public static function invoiceFieldPermissions(): array
    {
        return [
            self::ViewInvoicePatientIdNumber,
            self::ViewInvoiceDiagnosis,
            self::ViewInvoiceReferralNumber,
            self::ViewInvoiceInsurance,
            self::ViewInvoiceRoomWard,
            self::ViewInvoiceAge,
            self::ViewInvoiceDuration,
            self::ViewInvoiceRemainingDays,
            self::ViewInvoiceCostPerDay,
            self::ViewInvoicePatientType,
            self::ViewInvoiceSectionDaily,
            self::ViewInvoiceSectionLocalMed,
            self::ViewInvoiceSectionImportedMed,
            self::ViewInvoiceSectionSupplies,
            self::ViewInvoiceSectionLab,
            self::ViewInvoiceSectionOther,
            self::ViewInvoiceDiscounts,
            self::ViewInvoiceGrandTotal,
            self::ViewInvoiceHioExport,
        ];
    }
}
