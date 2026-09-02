<?php

namespace App\Enums;

enum MedicalReportType: string
{
    case Inpatient = 'inpatient';
    case Radiology = 'radiology';

    public function label(): string
    {
        return match ($this) {
            self::Inpatient => 'تقرير طبي لمريض داخلي',
            self::Radiology => 'تقرير قسم الأشعة',
        };
    }
}
