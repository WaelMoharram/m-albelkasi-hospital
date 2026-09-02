<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير طبي لمريض داخلي — {{ $report->admission->patient->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            background: #fff;
            direction: rtl;
        }

        @page { size: A4 portrait; margin: 12mm; }

        .no-print {
            position: fixed; top: 10px; left: 10px; z-index: 10;
            background: #1a3c6e; color: #fff; border: none; border-radius: 4px;
            padding: 8px 16px; font-size: 10pt; font-family: inherit; cursor: pointer;
        }
        @media print { .no-print { display: none !important; } }

        .header {
            display: table; width: 100%;
            border-bottom: 2pt solid #1a3c6e;
            padding-bottom: 8pt; margin-bottom: 12pt;
        }
        .header-right { display: table-cell; vertical-align: top; width: 60%; }
        .header-left  { display: table-cell; vertical-align: top; width: 40%; text-align: left; }
        .logo-wrap img { max-height: 55pt; max-width: 120pt; margin-bottom: 3pt; }
        .hosp-name  { font-size: 14pt; font-weight: bold; color: #1a3c6e; }
        .hosp-meta  { font-size: 7.5pt; color: #555; margin-top: 2pt; line-height: 1.5; }
        .report-label { font-size: 16pt; font-weight: bold; color: #1a3c6e; text-align: left; }
        .report-sub   { font-size: 9pt; color: #555; text-align: left; margin-top: 3pt; }

        .patient-box {
            border: 0.5pt solid #b0b8c8;
            border-radius: 3pt;
            margin-bottom: 12pt;
            padding: 6pt 8pt 3pt 8pt;
        }
        .p-line { margin-bottom: 3pt; }
        .p-field {
            display: inline-block;
            background: #eef2fa;
            border: 0.5pt solid #c3cee0;
            border-radius: 9pt;
            padding: 2pt 8pt;
            margin-left: 5pt;
        }
        .p-flabel { font-size: 7pt; color: #5a6b8c; font-weight: bold; }
        .p-fvalue { font-size: 9pt; font-weight: bold; color: #1a3c6e; margin-right: 3pt; }

        .section { margin-bottom: 12pt; }
        .section-title {
            font-size: 10pt; font-weight: bold; color: #1a3c6e;
            border-bottom: 1pt solid #1a3c6e;
            padding-bottom: 2pt; margin-bottom: 5pt;
        }
        .section-body {
            font-size: 10pt; line-height: 1.8; white-space: pre-wrap;
            min-height: 20pt;
        }

        .sig-wrap { display: table; width: 100%; margin-top: 30pt; }
        .sig-cell { display: table-cell; width: 50%; text-align: center; }
        .sig-line {
            border-top: 0.5pt solid #555;
            padding-top: 4pt; font-size: 8.5pt; color: #444; margin: 0 20pt;
        }

        .footer {
            margin-top: 20pt; border-top: 0.5pt solid #b0b8c8;
            padding-top: 5pt; display: table; width: 100%;
        }
        .footer-right { display: table-cell; font-size: 7.5pt; color: #666; }
        .footer-left  { display: table-cell; text-align: left; font-size: 7.5pt; color: #666; }
    </style>
</head>
<body>
@php
    use App\Models\Setting;

    $admission = $report->admission;
    $patient   = $admission->patient;

    $hospName    = Setting::getValue('hospital_name', config('app.name'));
    $hospLogo    = Setting::getValue('hospital_logo');
    $hospAddress = Setting::getValue('hospital_address');
    $hospPhones  = Setting::getValue('hospital_phones');
    $hospPoBox   = Setting::getValue('hospital_po_box');
    $hospCommReg = Setting::getValue('hospital_commercial_reg');

    $age = $patient->dob ? (int) $patient->dob->diffInYears($admission->admission_date) : null;
    $genderLabel = match ($patient->gender) {
        'male'   => 'ذكر',
        'female' => 'أنثى',
        default  => '—',
    };

    $logoBase64 = null;
    if ($hospLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($hospLogo)) {
        $logoPath   = \Illuminate\Support\Facades\Storage::disk('public')->path($hospLogo);
        $mime       = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp

<button type="button" class="no-print" onclick="window.print()">طباعة</button>

<div class="header">
    <div class="header-right">
        @if($logoBase64)
        <div class="logo-wrap"><img src="{{ $logoBase64 }}" alt="Logo"></div>
        @endif
        <div class="hosp-name">{{ $hospName }}</div>
        <div class="hosp-meta">
            @if($hospPoBox)ص.ب: {{ $hospPoBox }}&nbsp;&nbsp;@endif
            @if($hospCommReg)س.ت: {{ $hospCommReg }}@endif
            @if($hospAddress)<br>{{ $hospAddress }}@endif
            @if($hospPhones)<br>{{ $hospPhones }}@endif
        </div>
    </div>
    <div class="header-left">
        <div class="report-label">تقرير طبي لمريض داخلي</div>
        <div class="report-sub">
            رقم التقرير: {{ str_pad($report->id, 6, '0', STR_PAD_LEFT) }}<br>
            تاريخ التحرير: {{ ($report->report_date ?? $report->created_at)->format('d/m/Y') }}
        </div>
    </div>
</div>

<div class="patient-box">
    <div class="p-line">
        <span class="p-field"><span class="p-flabel">اسم المريض: </span><span class="p-fvalue">{{ $patient->name }}</span></span>
        <span class="p-field"><span class="p-flabel">السن: </span><span class="p-fvalue">{{ $age !== null ? $age . ' سنة' : '—' }}</span></span>
        <span class="p-field"><span class="p-flabel">النوع: </span><span class="p-fvalue">{{ $genderLabel }}</span></span>
    </div>
    <div class="p-line">
        <span class="p-field"><span class="p-flabel">تاريخ الدخول: </span><span class="p-fvalue">{{ $admission->admission_date->format('d/m/Y') }}</span></span>
        <span class="p-field"><span class="p-flabel">تاريخ الخروج: </span><span class="p-fvalue">{{ $admission->discharge_date?->format('d/m/Y') ?? '—' }}</span></span>
    </div>
</div>

<div class="section">
    <div class="section-title">التشخيص</div>
    <div class="section-body">{{ $report->diagnosis ?: '—' }}</div>
</div>

<div class="section">
    <div class="section-title">ما تم إجراؤه للمريض</div>
    <div class="section-body">{{ $report->procedure_notes ?: '—' }}</div>
</div>

<div class="section">
    <div class="section-title">ملاحظات</div>
    <div class="section-body">{{ $report->remarks ?: '—' }}</div>
</div>

<div class="sig-wrap">
    <div class="sig-cell"><div class="sig-line">الطبيب المعالج: {{ $report->referring_doctor ?: '..........................' }}</div></div>
    <div class="sig-cell"><div class="sig-line">التوقيع</div></div>
</div>

<div class="footer">
    <div class="footer-right">
        @if($hospAddress){{ $hospAddress }}&nbsp;@endif
        @if($hospPhones) — {{ $hospPhones }}@endif
    </div>
    <div class="footer-left">
        تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

</body>
</html>
