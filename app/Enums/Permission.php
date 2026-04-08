<?php

namespace App\Enums;

enum Permission: string
{
    // User management
    case ManageUsers = 'manage_users';
    case AssignRoles = 'assign_roles';

    // Catalog
    case ManageCatalog = 'manage_catalog';

    // Patients
    case RegisterPatients = 'register_patients';
    case ViewPatients = 'view_patients';

    // Admissions
    case ManageAdmissions = 'manage_admissions';
    case ViewAdmissions = 'view_admissions';

    // Invoices
    case ViewInvoices = 'view_invoices';
    case CreateInvoices = 'create_invoices';
    case EditInvoices = 'edit_invoices';
    case DeleteInvoices = 'delete_invoices';
    case PrintInvoices = 'print_invoices';
    case ConfirmPayment = 'confirm_payment';
    case AddInvoiceItems = 'add_invoice_items';

    // Reports
    case ViewReports = 'view_reports';
}
