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
hr.divider  { border: none; border-top: 1.5px solid #000; margin: 4px 0 8px; }

table.sum { width: 100%; border-collapse: collapse; font-size: 9pt; }
table.sum th, table.sum td {
    border: 0.7px solid #444;
    padding: 5px 6px;
    text-align: center;
    vertical-align: middle;
}
table.sum thead th { background: #d0d0d0; font-weight: bold; }
table.sum tfoot td { background: #c8c8c8; font-weight: bold; }
table.sum tbody tr:nth-child(even) { background: #f9f9f9; }

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
        <div>الهيئة العامة للتأمين الصحي — رئاسة الهيئة</div>
        <div class="title-main">
            كشف إجمالى الحالات المحولة لـ {{ $hospitalName }}
        </div>
        <div>{{ $insurance?->name }} — شهر {{ $monthName }} {{ $year }}</div>
    </div>
    <div class="header-right">
        <div>{{ $hospitalName }}</div>
        @if($poBox)   <div>ب.ض: {{ $poBox }}</div>   @endif
        @if($crNumber)<div>س.ت: {{ $crNumber }}</div> @endif
    </div>
</div>
<hr class="divider">

<table class="sum">
    <thead>
        <tr>
            <th style="width:30px;">الكشف</th>
            <th>نوع الخدمة</th>
            <th>قانون المحاسبة</th>
            <th style="width:50px;">عدد الحالات</th>
            <th style="width:60px;">عدد أيام الإقامة</th>
            <th style="width:80px;">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
        <tr>
            <td>{{ $row['seq'] }}</td>
            <td>{{ $row['service_type'] }}</td>
            <td>{{ $row['law'] }}</td>
            <td>{{ $row['count'] }}</td>
            <td>{{ $row['days'] }}</td>
            <td>{{ number_format($row['amount'], 3) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">الإجمالى</td>
            <td>{{ $totals['count'] }}</td>
            <td>{{ $totals['days'] }}</td>
            <td>{{ number_format($totals['amount'], 3) }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <div class="sig-cell">
        <div class="sig-line"></div>
        <div>{{ $sigDirector }}</div>
    </div>
    <div class="sig-cell" style="text-align:left; font-size:7.5pt; color:#666; padding-top:30px;">
        تاريخ الطباعة: {{ now()->format('Y/m/d') }}
    </div>
</div>
</body>
</html>
