<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 7.5pt;
    direction: rtl;
    color: #000;
}
@page {
    size: A3 landscape;
    margin: 10mm 8mm;
}
.page-break { page-break-before: always; }

/* ── Header ─────────────────────────────── */
.header-wrap {
    display: table;
    width: 100%;
    margin-bottom: 4px;
}
.header-logo  { display: table-cell; width: 60px; vertical-align: middle; }
.header-logo img { max-height: 50px; max-width: 55px; }
.header-center { display: table-cell; text-align: center; vertical-align: middle; }
.header-center .title-main { font-size: 10pt; font-weight: bold; }
.header-center .title-sub  { font-size: 8pt; }
.header-right  { display: table-cell; width: 120px; text-align: right; vertical-align: top; font-size: 7pt; line-height: 1.5; }
hr.divider { border: none; border-top: 1.5px solid #000; margin: 3px 0; }

/* ── Table ───────────────────────────────── */
table.claim {
    width: 100%;
    border-collapse: collapse;
    font-size: 7pt;
    margin-top: 4px;
}
table.claim th,
table.claim td {
    border: 0.5px solid #444;
    padding: 2px 2.5px;
    text-align: center;
    vertical-align: middle;
}
table.claim thead th {
    background-color: #d0d0d0;
    font-size: 6.5pt;
    font-weight: bold;
}
table.claim thead tr.sub-head th {
    background-color: #e8e8e8;
    font-size: 6pt;
}
table.claim tbody tr:nth-child(even) { background: #f9f9f9; }
table.claim tfoot td {
    background: #c8c8c8;
    font-weight: bold;
}
td.name { text-align: right; padding-right: 3px; }

/* ── Footer ──────────────────────────────── */
.page-footer {
    margin-top: 6px;
    display: table;
    width: 100%;
    font-size: 7pt;
}
.sig-cell { display: table-cell; text-align: center; width: 33%; }
.sig-line  { border-top: 1px solid #000; display: inline-block; width: 80%; margin-top: 18px; }
</style>
</head>
<body>
@php
    $chunks     = $rows->chunk(10);
    $totalPages = $chunks->count();
    $hospitalName = $settings->get('hospital_name', config('app.name'));
    $poBox        = $settings->get('hospital_po_box', '');
    $crNumber     = $settings->get('hospital_commercial_reg', '');
    $address      = $settings->get('hospital_address', '');
    $phone1       = $settings->get('hospital_phones', '');
    $sigDirector  = $settings->get('invoice_approved_by', 'مدير المستشفى');
@endphp

@foreach ($chunks as $pageIndex => $chunk)
    @if ($pageIndex > 0)
        <div class="page-break"></div>
    @endif

    {{-- Header --}}
    <div class="header-wrap">
        <div class="header-logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="logo">
            @endif
        </div>
        <div class="header-center">
            <div>الهيئة العامة للتأمين الصحي</div>
            <div>رئاسة الهيئة</div>
            <div class="title-main" style="margin-top:3px;">
                كشف رقم {{ $pageIndex + 1 }} الحالات المحولة
                لـ {{ $hospitalName }}
                شهر {{ $monthName }} {{ $year }}
            </div>
            <div class="title-sub">{{ $insurance?->name }}</div>
        </div>
        <div class="header-right">
            <div>{{ $hospitalName }}</div>
            @if($poBox) <div>ب.ض: {{ $poBox }}</div> @endif
            @if($crNumber) <div>س.ت: {{ $crNumber }}</div> @endif
        </div>
    </div>
    <hr class="divider">

    {{-- Table --}}
    <table class="claim">
        <thead>
            <tr>
                <th rowspan="2" style="width:14px;">#</th>
                <th rowspan="2" style="width:72px;">اسم المنتفع</th>
                <th rowspan="2" style="width:26px;">بطاقة صحية</th>
                <th rowspan="2" style="width:30px;">تاريخ الدخول</th>
                <th rowspan="2" style="width:30px;">تاريخ الخروج</th>
                <th rowspan="2" style="width:16px;">المدة</th>
                @foreach ($categories as $cat)
                    <th style="width:28px;">{{ $cat->name }}</th>
                @endforeach
                <th rowspan="2" style="width:30px;">إجمالي<br>الإقامة</th>
                <th rowspan="2" style="width:28px;">تحاليل</th>
                <th rowspan="2" style="width:34px;">أدوية محلية<br>بعد خصم {{ number_format($local_discount, 0) }}%</th>
                <th rowspan="2" style="width:34px;">أدوية مستوردة<br>بعد خصم {{ number_format($imported_discount, 0) }}%</th>
                <th rowspan="2" style="width:22px;">مستلزمات</th>
                <th rowspan="2" style="width:34px;">الإجمالي<br>العام</th>
                <th rowspan="2" style="width:26px;">المعدل<br>اليومي</th>
            </tr>
            <tr></tr>
        </thead>
        <tbody>
            @foreach ($chunk as $row)
            <tr>
                <td>{{ $row['seq'] }}</td>
                <td class="name">{{ $row['patient']->name }}</td>
                <td>{{ $row['admission']->referral_number ?? '' }}</td>
                <td>{{ $row['admission']->admission_date->format('Y/m/d') }}</td>
                <td>{{ $row['admission']->discharge_date->format('Y/m/d') }}</td>
                <td>{{ $row['days'] }}</td>
                @foreach ($categories as $cat)
                    <td>{{ $row['by_category'][$cat->id] > 0 ? number_format($row['by_category'][$cat->id], 2) : '' }}</td>
                @endforeach
                <td><strong>{{ number_format($row['stay_subtotal'], 2) }}</strong></td>
                <td>{{ $row['labs'] > 0 ? number_format($row['labs'], 2) : '' }}</td>
                <td>{{ $row['local_meds'] > 0 ? number_format($row['local_meds'], 3) : '' }}</td>
                <td>{{ $row['imported_meds'] > 0 ? number_format($row['imported_meds'], 3) : '' }}</td>
                <td></td>
                <td><strong>{{ number_format($row['grand_total'], 3) }}</strong></td>
                <td>{{ number_format($row['per_day'], 6) }}</td>
            </tr>
            @endforeach

            {{-- Last page: show grand totals in tfoot --}}
            @if ($pageIndex + 1 === $totalPages)
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">الإجمالي</td>
                    <td>{{ $totals['days'] }}</td>
                    @foreach ($categories as $cat)
                        <td>{{ number_format($totals['by_category'][$cat->id] ?? 0, 2) }}</td>
                    @endforeach
                    <td>{{ number_format($totals['stay_subtotal'], 2) }}</td>
                    <td>{{ number_format($totals['labs'], 2) }}</td>
                    <td>{{ number_format($totals['local_meds'], 3) }}</td>
                    <td>{{ number_format($totals['imported_meds'], 3) }}</td>
                    <td>—</td>
                    <td>{{ number_format($totals['grand_total'], 3) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @else
            </tbody>
            @endif
        </table>

    {{-- Footer / Signature --}}
    <div class="page-footer">
        <div class="sig-cell">
            <div class="sig-line"></div>
            <div>{{ $sigDirector }}</div>
        </div>
        <div class="sig-cell" style="text-align:center;">
            <div style="font-size:6.5pt; color:#555;">
                كشف رقم ({{ $pageIndex + 1 }}) من ({{ $totalPages }})
                &nbsp;&nbsp;|&nbsp;&nbsp;
                تاريخ الطباعة: {{ now()->format('Y/m/d') }}
            </div>
        </div>
        <div class="sig-cell"></div>
    </div>

@endforeach
</body>
</html>
