<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; direction: rtl; color: #000; }
@page { size: A4 portrait; margin: 15mm 12mm; }

.header-wrap  { display: table; width: 100%; margin-bottom: 6px; }
.header-logo  { display: table-cell; width: 55px; vertical-align: middle; }
.header-logo img { max-height: 50px; max-width: 50px; }
.header-center { display: table-cell; text-align: center; vertical-align: middle; }
.header-right  { display: table-cell; width: 110px; text-align: right; vertical-align: top; font-size: 7.5pt; line-height: 1.6; }
.title-main { font-size: 11pt; font-weight: bold; margin-top: 4px; }
hr.divider  { border: none; border-top: 1.5px solid #000; margin: 4px 0 10px; }

.section-title {
    background: #d0d0d0;
    font-weight: bold;
    font-size: 10pt;
    padding: 5px 8px;
    margin: 10px 0 0;
    border: 0.7px solid #555;
}
table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
table.data th, table.data td {
    border: 0.7px solid #555;
    padding: 5px 8px;
    vertical-align: middle;
}
table.data th { text-align: right; font-weight: normal; width: 70%; background: #f5f5f5; }
table.data td { text-align: center; font-weight: bold; width: 30%; }
.highlight { background: #e8f4e8; }

.footer { margin-top: 30px; display: table; width: 100%; font-size: 8.5pt; }
.sig-cell { display: table-cell; text-align: center; width: 50%; }
.sig-line  { border-top: 1px solid #000; display: inline-block; width: 65%; margin-top: 25px; }
</style>
</head>
<body>
@php
    $hospitalName = $settings->get('hospital_name', config('app.name'));
    $poBox        = $settings->get('hospital_po_box', '');
    $crNumber     = $settings->get('hospital_commercial_reg', '');
    $sigDirector  = $settings->get('invoice_approved_by', 'مدير المستشفى');
@endphp

<div class="header-wrap">
    <div class="header-logo">
        @if ($logo) <img src="{{ $logo }}" alt="logo"> @endif
    </div>
    <div class="header-center">
        <div class="title-main">مؤشرات الأداء للرعاية المركزة</div>
        <div>{{ $hospitalName }}</div>
        <div>شهر {{ $monthName }} {{ $year }}</div>
    </div>
    <div class="header-right">
        <div>{{ $hospitalName }}</div>
        @if($poBox)   <div>ب.ض: {{ $poBox }}</div>   @endif
        @if($crNumber)<div>س.ت: {{ $crNumber }}</div> @endif
    </div>
</div>
<hr class="divider">

{{-- Basic Data --}}
<div class="section-title">البيانات الأساسية لحساب المؤشرات</div>
<table class="data">
    <tr><th>عدد أيام الشهر</th><td>{{ $days_in_month }}</td></tr>
    <tr><th>عدد أسرة الرعاية المركزة</th><td>{{ $icu_beds }}</td></tr>
    <tr><th>إجمالي أيام الإقامة المتاحة خلال الشهر</th><td>{{ $available_days }}</td></tr>
    <tr><th>عدد المرضى خلال الشهر</th><td>{{ $patient_count }}</td></tr>
    <tr><th>عدد أيام إقامة المرضى بالرعاية المركزة</th><td>{{ $stay_days }}</td></tr>
    <tr><th>عدد الأيام المتاحة المتبقية خلال الشهر</th><td>{{ $remaining_days }}</td></tr>
    <tr><th>عدد الوفيات خلال 24 ساعة من الدخول للرعاية</th><td>{{ $deaths_24h }}</td></tr>
    <tr><th>عدد الوفيات بالرعاية المركزة</th><td>{{ $deaths }}</td></tr>
</table>

{{-- Indicators --}}
<div class="section-title" style="margin-top:14px;">مؤشرات الأداء</div>
<table class="data">
    <tr>
        <th>متوسط التردد اليومي على الرعاية المركزة</th>
        <td>{{ number_format($avg_daily_freq, 6) }}</td>
    </tr>
    <tr>
        <th>معدل وفيات الرعاية المركزة</th>
        <td>{{ number_format($mortality_rate, 6) }}</td>
    </tr>
    <tr>
        <th>متوسط فترة الإقامة بالرعاية المركزة</th>
        <td>{{ number_format($avg_stay, 6) }}</td>
    </tr>
    <tr>
        <th>معدل دوران السرير بالرعاية المركزة</th>
        <td>{{ number_format($bed_turnover, 6) }}</td>
    </tr>
    <tr class="highlight">
        <th>معدل إشغال أسرة الرعاية المركزة</th>
        <td>{{ number_format($occupancy_rate, 6) }}</td>
    </tr>
</table>

<div class="footer">
    <div class="sig-cell">
        <div class="sig-line"></div>
        <div>{{ $sigDirector }}</div>
    </div>
    <div class="sig-cell" style="text-align:left; font-size:7pt; color:#666; padding-top:30px;">
        تاريخ الطباعة: {{ now()->format('Y/m/d') }}
    </div>
</div>
</body>
</html>
