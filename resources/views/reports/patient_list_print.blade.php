<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8pt;
    direction: rtl;
    color: #000;
}
@page {
    size: A4 landscape;
    margin: 10mm 10mm;
}
.page-break { page-break-before: always; }

.header-wrap {
    display: table;
    width: 100%;
    margin-bottom: 5px;
}
.header-logo   { display: table-cell; width: 55px; vertical-align: middle; }
.header-logo img { max-height: 48px; max-width: 50px; }
.header-center { display: table-cell; text-align: center; vertical-align: middle; }
.header-right  { display: table-cell; width: 110px; text-align: right; vertical-align: top; font-size: 7pt; line-height: 1.6; }
.title-main    { font-size: 10pt; font-weight: bold; margin-top: 3px; }
hr.divider     { border: none; border-top: 1.5px solid #000; margin: 3px 0 5px; }

table.list {
    width: 100%;
    border-collapse: collapse;
    font-size: 8pt;
}
table.list th,
table.list td {
    border: 0.5px solid #555;
    padding: 2.5px 3px;
    text-align: center;
    vertical-align: middle;
}
table.list thead th {
    background: #d0d0d0;
    font-weight: bold;
    font-size: 7.5pt;
}
table.list tbody tr:nth-child(even) { background: #f9f9f9; }
table.list tfoot td {
    background: #c8c8c8;
    font-weight: bold;
}
td.name { text-align: right; padding-right: 4px; }

.page-footer {
    margin-top: 8px;
    display: table;
    width: 100%;
    font-size: 7.5pt;
}
.sig-cell  { display: table-cell; text-align: center; width: 33%; }
.sig-line  { border-top: 1px solid #000; display: inline-block; width: 70%; margin-top: 20px; }
</style>
</head>
<body>
@php
    $chunks       = $rows->chunk(20);
    $totalPages   = $chunks->count();
    $hospitalName = $settings->get('hospital_name', config('app.name'));
    $poBox        = $settings->get('hospital_po_box', '');
    $crNumber     = $settings->get('hospital_commercial_reg', '');
    $sigDirector  = $settings->get('invoice_approved_by', 'مدير المستشفى');
@endphp

@foreach ($chunks as $pageIndex => $chunk)
    @if ($pageIndex > 0)
        <div class="page-break"></div>
    @endif

    <div class="header-wrap">
        <div class="header-logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="logo">
            @endif
        </div>
        <div class="header-center">
            <div>الهيئة العامة للتأمين الصحي</div>
            <div>رئاسة الهيئة</div>
            <div class="title-main">
                كشف إجمالى بالحالات المحولة لـ {{ $hospitalName }}
                شهر {{ $monthName }} {{ $year }}
            </div>
            <div style="font-size:8.5pt;">{{ $insurance?->name }}</div>
        </div>
        <div class="header-right">
            <div>{{ $hospitalName }}</div>
            @if($poBox)   <div>ب.ض: {{ $poBox }}</div>   @endif
            @if($crNumber)<div>س.ت: {{ $crNumber }}</div> @endif
        </div>
    </div>
    <hr class="divider">

    <table class="list">
        <thead>
            <tr>
                <th style="width:16px;">م</th>
                <th style="width:70px;">اسم المريض</th>
                <th style="width:30px;">تاريخ الميلاد</th>
                <th style="width:18px;">السن</th>
                <th style="width:30px;">تاريخ الدخول</th>
                <th style="width:30px;">تاريخ الخروج</th>
                <th style="width:18px;">مدة الإقامة</th>
                <th style="width:32px;">رقم خ. التحويل</th>
                <th style="width:50px;">جهة التحويل</th>
                <th style="width:38px;">قيمة الفاتورة</th>
                <th style="width:28px;">المعدل اليومي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chunk as $row)
            <tr>
                <td>{{ $row['seq'] }}</td>
                <td class="name">{{ $row['patient']->name }}</td>
                <td>{{ $row['patient']->dob?->format('Y/m/d') ?? '' }}</td>
                <td>{{ $row['age'] ?? '' }}</td>
                <td>{{ $row['admission']->admission_date->format('Y/m/d') }}</td>
                <td>{{ $row['admission']->discharge_date->format('Y/m/d') }}</td>
                <td>{{ $row['days'] }}</td>
                <td>{{ $row['referral_number'] ?? '' }}</td>
                <td>{{ $row['referral_source'] ?? '' }}</td>
                <td><strong>{{ number_format($row['invoice_total'], 2) }}</strong></td>
                <td>{{ number_format($row['per_day'], 6) }}</td>
            </tr>
            @endforeach

            @if ($pageIndex + 1 === $totalPages)
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6">الإجمالى</td>
                    <td>{{ $totals['days'] }}</td>
                    <td colspan="2"></td>
                    <td>{{ number_format($totals['invoice_total'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @else
            </tbody>
            @endif
        </table>

    <div class="page-footer">
        <div class="sig-cell">
            <div class="sig-line"></div>
            <div>{{ $sigDirector }}</div>
        </div>
        <div class="sig-cell" style="text-align:center;">
            <div style="font-size:6.5pt; color:#555;">
                صفحة ({{ $pageIndex + 1 }}) من ({{ $totalPages }})
                &nbsp;&nbsp;|&nbsp;&nbsp;
                تاريخ الطباعة: {{ now()->format('Y/m/d') }}
            </div>
        </div>
        <div class="sig-cell"></div>
    </div>

@endforeach
</body>
</html>
