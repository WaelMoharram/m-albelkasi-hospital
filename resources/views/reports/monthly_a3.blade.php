<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التقرير الشهري — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY') }}</title>
    <style>
        /* ── Reset ──────────────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1a1a1a;
            background: #fff;
            direction: rtl;
        }

        /* ── Fixed header — repeats on every page ───────────────────────── */
        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 44pt;
            background: #fff;
            border-bottom: 2pt solid #0d6efd;
            padding: 0 16pt;
        }

        .page-header-inner {
            display: table;
            width: 100%;
            height: 44pt;
        }

        .header-right { display: table-cell; vertical-align: middle; }
        .header-left  { display: table-cell; vertical-align: middle; text-align: left; }

        .hospital-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0d6efd;
        }

        .report-subtitle {
            font-size: 8pt;
            color: #6c757d;
            margin-top: 1pt;
        }

        .report-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            text-align: left;
        }

        .report-period {
            font-size: 9pt;
            color: #0d6efd;
            font-weight: bold;
            margin-top: 1pt;
            text-align: left;
        }

        /* ── Fixed footer ────────────────────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 18pt;
            border-top: 0.5pt solid #dee2e6;
            padding: 0 16pt;
            font-size: 7pt;
            color: #6c757d;
        }

        .footer-inner {
            display: table;
            width: 100%;
            height: 18pt;
        }

        .footer-right { display: table-cell; vertical-align: middle; }
        .footer-left  { display: table-cell; vertical-align: middle; text-align: left; }

        /* ── Main content ────────────────────────────────────────────────── */
        .content {
            margin-top: 52pt;
            margin-bottom: 26pt;
            padding: 0 16pt;
        }

        /* ── Stats bar ──────────────────────────────────────────────────── */
        .stats-bar {
            display: table;
            width: 100%;
            margin-bottom: 10pt;
        }

        .stat-cell {
            display: table-cell;
            width: 20%;
            padding: 5pt 8pt;
            background: #f8f9fa;
            border: 0.5pt solid #dee2e6;
            vertical-align: middle;
        }

        .stat-cell + .stat-cell { border-right: none; }

        .stat-label { font-size: 6.5pt; text-transform: uppercase; color: #6c757d; font-weight: bold; letter-spacing: 0.3pt; }
        .stat-value { font-size: 11pt; font-weight: bold; color: #1a1a1a; margin-top: 1pt; }

        /* ── Main table ─────────────────────────────────────────────────── */
        table.main {
            width: 100%;
            border-collapse: collapse;
        }

        table.main thead tr {
            background: #1a1a2e;
            color: #fff;
        }

        table.main thead th {
            padding: 5pt 4pt;
            font-size: 7pt;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            border: none;
        }

        table.main thead th.text-left  { text-align: left; }
        table.main thead th.text-center { text-align: center; }

        table.main tbody tr { page-break-inside: avoid; }

        table.main tbody tr:nth-child(even) td { background: #f8f9fa; }
        table.main tbody tr:nth-child(odd)  td { background: #ffffff; }

        table.main tbody td {
            padding: 4pt 4pt;
            font-size: 8pt;
            border-bottom: 0.25pt solid #e9ecef;
            vertical-align: middle;
            text-align: right;
        }

        table.main tbody td.text-left  { text-align: left; }
        table.main tbody td.text-center { text-align: center; }

        .patient-name { font-weight: bold; font-size: 8pt; }
        .patient-id   { font-size: 6.5pt; color: #6c757d; font-family: Courier, monospace; }

        .badge-active {
            display: inline-block;
            background: #d1e7dd;
            color: #0a3622;
            font-size: 6pt;
            font-weight: bold;
            padding: 1pt 4pt;
            border-radius: 2pt;
        }

        .zero { color: #ced4da; }

        /* ── Section colour bars ────────────────────────────────────────── */
        .col-local    { background: #1a6b3a !important; }
        .col-imported { background: #856404 !important; }
        .col-lab      { background: #055160 !important; }
        .col-radio    { background: #3d0a6e !important; }
        .col-daily    { background: #41464b !important; }
        .col-total    { background: #0d6efd !important; }

        /* ── Totals footer row ──────────────────────────────────────────── */
        table.main tfoot td {
            padding: 5pt 4pt;
            font-size: 8.5pt;
            font-weight: bold;
            border-top: 1.5pt solid #1a1a2e;
            background: #1a1a2e;
            color: #fff;
            text-align: right;
        }

        table.main tfoot td.text-center { text-align: center; }

        /* ── No-data message ────────────────────────────────────────────── */
        .no-data {
            text-align: center;
            padding: 40pt;
            color: #6c757d;
            font-size: 11pt;
        }
    </style>
</head>
<body>

@php
    use Carbon\Carbon;
    $monthLabel  = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    $printedAt   = now()->format('d/m/Y  H:i');
    $totalRows   = $rows->count();
@endphp

{{-- ── Fixed page header ──────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header-inner">
        <div class="header-right">
            <div class="hospital-name">{{ config('app.name', 'Hospital') }}</div>
            <div class="report-subtitle">نظام فوترة التأمين</div>
        </div>
        <div class="header-left">
            <div class="report-title">التقرير الشهري للفوترة</div>
            <div class="report-period">{{ strtoupper($monthLabel) }}</div>
        </div>
    </div>
</div>

{{-- ── Fixed page footer ──────────────────────────────────────────────── --}}
<div class="page-footer">
    <div class="footer-inner">
        <div class="footer-right">
            تاريخ الطباعة: {{ $printedAt }} &nbsp;·&nbsp; {{ $totalRows }} إدخال
        </div>
        <div class="footer-left" id="page-num-placeholder">
            {{-- page number injected by dompdf PHP script --}}
        </div>
    </div>
</div>

{{-- ── Page number script (requires isPhpEnabled = true) ─────────────── --}}
<script type="text/php">
    if (isset($pdf)) {
        $pageWidth  = $pdf->get_width();
        $pageHeight = $pdf->get_height();
        $font       = $fontMetrics->get_font("DejaVu Sans, helvetica", "normal");
        $size       = 7;
        $color      = [0.42, 0.45, 0.49];
        $text       = "Page {PAGE_NUM} / {PAGE_COUNT}";
        $x          = 16;
        $y          = $pageHeight - 14;
        $pdf->page_text($x, $y, $text, $font, $size, $color);
    }
</script>

{{-- ── Content ────────────────────────────────────────────────────────── --}}
<div class="content">

    {{-- Stats summary bar --}}
    @if($rows->isNotEmpty())
    <div class="stats-bar">
        <div class="stat-cell">
            <div class="stat-label">الإدخالات</div>
            <div class="stat-value">{{ $totalRows }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">أدوية محلية</div>
            <div class="stat-value">{{ number_format($totals['local_med'], 2) }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">أدوية مستوردة</div>
            <div class="stat-value">{{ number_format($totals['imported_med'], 2) }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">مخبر + أشعة</div>
            <div class="stat-value">{{ number_format($totals['lab'] + $totals['radiology'], 2) }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">الإجمالي الكلي</div>
            <div class="stat-value" style="color:#0d6efd;">{{ number_format($totals['grand_total'], 2) }}</div>
        </div>
    </div>
    @endif

    {{-- Main table --}}
    @if($rows->isEmpty())
        <div class="no-data">لا توجد إدخالات لشهر {{ $monthLabel }}.</div>
    @else
    <table class="main">
        <thead>
            <tr>
                <th class="text-center" style="width:20pt;">#</th>
                <th style="width:110pt;">المريض</th>
                <th style="width:90pt;">شركة التأمين</th>
                <th class="text-center" style="width:52pt;">تاريخ الإدخال</th>
                <th class="text-center" style="width:52pt;">تاريخ الخروج</th>
                <th class="col-local"    style="width:68pt;">أدوية<br>محلية</th>
                <th class="col-imported" style="width:68pt;">أدوية<br>مستوردة</th>
                <th class="col-lab"      style="width:58pt;">تحاليل<br>مخبرية</th>
                <th class="col-radio"    style="width:58pt;">أشعة</th>
                <th class="col-daily"    style="width:58pt;">رسوم<br>يومية</th>
                <th class="col-total"    style="width:72pt;">الإجمالي<br>الكلي</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $i => $row)
            @php $adm = $row['admission']; @endphp
            <tr>
                <td class="text-center" style="color:#6c757d; font-size:7pt;">{{ $i + 1 }}</td>

                <td>
                    <div class="patient-name">{{ $row['patient']->name }}</div>
                    <div class="patient-id">{{ $row['patient']->national_id }}</div>
                </td>

                <td style="font-size:7.5pt;">{{ $row['insurance']->name ?? '—' }}</td>

                <td class="text-center" style="font-size:7.5pt;">
                    {{ $adm->admission_date->format('d/m/Y') }}
                </td>

                <td class="text-center" style="font-size:7.5pt;">
                    @if($adm->discharge_date)
                        {{ $adm->discharge_date->format('d/m/Y') }}
                    @else
                        <span class="badge-active">نشط</span>
                    @endif
                </td>

                <td>
                    @if($row['local_med'] > 0)
                        {{ number_format($row['local_med'], 2) }}
                    @else
                        <span class="zero">—</span>
                    @endif
                </td>

                <td>
                    @if($row['imported_med'] > 0)
                        {{ number_format($row['imported_med'], 2) }}
                    @else
                        <span class="zero">—</span>
                    @endif
                </td>

                <td>
                    @if($row['lab'] > 0)
                        {{ number_format($row['lab'], 2) }}
                    @else
                        <span class="zero">—</span>
                    @endif
                </td>

                <td>
                    @if($row['radiology'] > 0)
                        {{ number_format($row['radiology'], 2) }}
                    @else
                        <span class="zero">—</span>
                    @endif
                </td>

                <td>
                    @if($row['daily'] > 0)
                        {{ number_format($row['daily'], 2) }}
                    @else
                        <span class="zero">—</span>
                    @endif
                </td>

                <td style="font-weight:bold;">
                    {{ number_format($row['grand_total'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">الإجماليات ({{ $totalRows }} إدخال)</td>
                <td>{{ number_format($totals['local_med'],    2) }}</td>
                <td>{{ number_format($totals['imported_med'], 2) }}</td>
                <td>{{ number_format($totals['lab'],          2) }}</td>
                <td>{{ number_format($totals['radiology'],    2) }}</td>
                <td>{{ number_format($totals['daily'],        2) }}</td>
                <td style="font-size:10pt;">
                    {{ number_format($totals['grand_total'], 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
    @endif

</div>
</body>
</html>
