<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة #{{ $invoice->id }}</title>
    <style>
        /* ── Reset & base ───────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            background: #fff;
            direction: rtl;
        }

        /* ── Page layout ────────────────────────────────────────────────── */
        .page {
            width: 100%;
            padding: 18mm 16mm 14mm 16mm;
        }

        /* ── Header ─────────────────────────────────────────────────────── */
        .header {
            border-bottom: 2.5pt solid #0d6efd;
            padding-bottom: 8pt;
            margin-bottom: 14pt;
        }

        .header-top {
            display: table;
            width: 100%;
        }

        .header-right { display: table-cell; vertical-align: middle; width: 55%; }
        .header-left  { display: table-cell; vertical-align: middle; width: 45%; text-align: left; }

        .hospital-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0d6efd;
            letter-spacing: 0.5pt;
        }

        .hospital-sub {
            font-size: 8pt;
            color: #6c757d;
            margin-top: 2pt;
        }

        .invoice-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1a1a1a;
            text-align: left;
        }

        .invoice-meta {
            font-size: 8pt;
            color: #6c757d;
            margin-top: 3pt;
            text-align: left;
        }

        /* ── Status badge ───────────────────────────────────────────────── */
        .badge-final {
            display: inline-block;
            background: #198754;
            color: #fff;
            font-size: 7pt;
            font-weight: bold;
            padding: 2pt 6pt;
            border-radius: 3pt;
        }

        .badge-draft {
            display: inline-block;
            background: #ffc107;
            color: #000;
            font-size: 7pt;
            font-weight: bold;
            padding: 2pt 6pt;
            border-radius: 3pt;
        }

        /* ── Patient & admission info ────────────────────────────────────── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 14pt;
            border: 0.5pt solid #dee2e6;
            border-radius: 4pt;
        }

        .info-grid-row { display: table-row; }

        .info-cell {
            display: table-cell;
            width: 33.33%;
            padding: 7pt 9pt;
            vertical-align: top;
            border-left: 0.5pt solid #dee2e6;
        }

        .info-cell:last-child { border-left: none; }

        .info-label {
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #6c757d;
            letter-spacing: 0.5pt;
            margin-bottom: 3pt;
        }

        .info-value  { font-size: 9.5pt; font-weight: bold; }
        .info-sub    { font-size: 8pt; color: #6c757d; margin-top: 1pt; }

        /* ── Section blocks ──────────────────────────────────────────────── */
        .section {
            margin-bottom: 12pt;
        }

        .section-header {
            background: #f8f9fa;
            border-right: 3pt solid #0d6efd;
            padding: 4pt 8pt;
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 0;
        }

        .section-header-local     { border-color: #198754; }
        .section-header-imported  { border-color: #ffc107; }
        .section-header-lab       { border-color: #0dcaf0; }
        .section-header-radiology { border-color: #6f42c1; }

        /* ── Tables ──────────────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8f9fa;
            border-top: 0.5pt solid #dee2e6;
            border-bottom: 0.5pt solid #dee2e6;
            font-size: 8pt;
            font-weight: bold;
            padding: 4pt 6pt;
            text-align: right;
            color: #495057;
        }

        tbody td {
            padding: 4pt 6pt;
            border-bottom: 0.25pt solid #f0f0f0;
            font-size: 9pt;
            vertical-align: middle;
            text-align: right;
        }

        .text-left   { text-align: left; }
        .text-center { text-align: center; }
        .fw-bold     { font-weight: bold; }

        /* ── Subtotal row ────────────────────────────────────────────────── */
        .subtotal-row td {
            background: #f8f9fa;
            border-top: 0.5pt solid #dee2e6;
            padding: 4pt 6pt;
            font-size: 8.5pt;
            font-weight: bold;
        }

        /* ── Empty section ───────────────────────────────────────────────── */
        .empty-row td {
            font-size: 8pt;
            color: #adb5bd;
            font-style: italic;
            padding: 5pt 6pt;
        }

        /* ── Grand total block ───────────────────────────────────────────── */
        .totals-block {
            margin-top: 16pt;
            border-top: 1.5pt solid #dee2e6;
            padding-top: 8pt;
        }

        .totals-table {
            width: 280pt;
            margin-right: auto;
        }

        .totals-table td {
            padding: 3pt 6pt;
            font-size: 9pt;
            border: none;
            text-align: right;
        }

        .grand-total-row td {
            padding-top: 6pt;
            border-top: 1pt solid #1a1a1a;
            font-size: 12pt;
            font-weight: bold;
        }

        /* ── Footer ──────────────────────────────────────────────────────── */
        .footer {
            margin-top: 24pt;
            border-top: 0.5pt solid #dee2e6;
            padding-top: 8pt;
            display: table;
            width: 100%;
        }

        .footer-right { display: table-cell; width: 50%; font-size: 8pt; color: #6c757d; }
        .footer-left  { display: table-cell; width: 50%; text-align: left; font-size: 8pt; color: #6c757d; }

        .signature-box {
            margin-top: 32pt;
            display: table;
            width: 100%;
        }

        .sig-cell {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding-top: 18pt;
            border-top: 0.5pt solid #495057;
            font-size: 8pt;
            color: #495057;
        }

        .watermark-draft {
            position: fixed;
            top: 42%;
            left: 15%;
            font-size: 72pt;
            font-weight: bold;
            color: rgba(255, 193, 7, 0.15);
            transform: rotate(-35deg);
            letter-spacing: 6pt;
            z-index: -1;
        }
    </style>
</head>
<body>
@php
    $admission = $invoice->admission;
    $patient   = $admission->patient;
    $grouped   = $invoice->items->groupBy('section');

    $sections = [
        'local_med'    => ['label' => 'أدوية محلية',    'css' => 'local'],
        'imported_med' => ['label' => 'أدوية مستوردة',  'css' => 'imported'],
        'lab'          => ['label' => 'تحاليل مخبرية',  'css' => 'lab'],
        'radiology'    => ['label' => 'أشعة',            'css' => 'radiology'],
    ];

    $billableTotal = $invoice->items->whereIn('section', array_keys($sections))->sum('total');
    $dailyTotal    = $invoice->items->where('section', 'daily')->sum('total');
@endphp

@if($invoice->status === 'draft')
<div class="watermark-draft">مسودة</div>
@endif

<div class="page">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-top">
            <div class="header-right">
                <div class="hospital-name">{{ config('app.name', 'Hospital') }}</div>
                <div class="hospital-sub">نظام فوترة التأمين</div>
            </div>
            <div class="header-left">
                <div class="invoice-title">فاتورة</div>
                <div class="invoice-meta">
                    #{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}&nbsp;&nbsp;
                    <span class="{{ $invoice->status === 'final' ? 'badge-final' : 'badge-draft' }}">
                        {{ $invoice->status === 'final' ? 'نهائي' : 'مسودة' }}
                    </span>
                </div>
                <div class="invoice-meta" style="margin-top:4pt;">
                    التاريخ: {{ $invoice->invoice_date->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Patient / Admission info ─────────────────────────────────── --}}
    <div class="info-grid">
        <div class="info-grid-row">
            <div class="info-cell">
                <div class="info-label">المريض</div>
                <div class="info-value">{{ $patient->name }}</div>
                <div class="info-sub">الرقم القومي: {{ $patient->national_id }}</div>
                <div class="info-sub">تاريخ الميلاد: {{ $patient->dob->format('d/m/Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">التأمين</div>
                <div class="info-value">{{ $patient->insuranceCompany->name ?? '—' }}</div>
                <div class="info-sub">البوليصة: {{ $patient->policy_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">الإدخال</div>
                <div class="info-value">#{{ $admission->id }}</div>
                <div class="info-sub">
                    تاريخ الإدخال: {{ $admission->admission_date->format('d/m/Y') }}
                </div>
                @if($admission->discharge_date)
                <div class="info-sub">
                    تاريخ الخروج: {{ $admission->discharge_date->format('d/m/Y') }}
                </div>
                @endif
                <div class="info-sub">
                    الغرفة {{ $admission->room ?? '—' }} / {{ $admission->ward ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4 Invoice Sections ───────────────────────────────────────── --}}
    @foreach ($sections as $key => $meta)
    @php $items = $grouped[$key] ?? collect(); @endphp
    <div class="section">
        <div class="section-header section-header-{{ $meta['css'] }}">
            {{ $meta['label'] }}
        </div>
        <table>
            <thead>
                <tr>
                    <th>الوصف</th>
                    @if($key === 'local_med' || $key === 'imported_med')
                    <th style="width:60pt;">الوحدة</th>
                    @endif
                    <th style="width:40pt;">الكمية</th>
                    <th style="width:80pt;">سعر الوحدة</th>
                    <th style="width:80pt;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                <tr>
                    <td>{{ $item->itemable->name ?? '—' }}</td>
                    @if($key === 'local_med' || $key === 'imported_med')
                    <td class="text-center">{{ $item->itemable->unit ?? '' }}</td>
                    @endif
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td class="fw-bold">{{ number_format($item->total, 2) }}</td>
                </tr>
                @empty
                <tr class="empty-row">
                    <td colspan="{{ ($key === 'local_med' || $key === 'imported_med') ? 5 : 4 }}">
                        لا يوجد بنود
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($items->isNotEmpty())
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="{{ ($key === 'local_med' || $key === 'imported_med') ? 4 : 3 }}">المجموع الفرعي</td>
                    <td>{{ number_format($items->sum('total'), 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @endforeach

    {{-- ── Totals block ────────────────────────────────────────────── --}}
    <div class="totals-block">
        <table class="totals-table">
            <tr>
                <td>الأدوية والخدمات</td>
                <td>{{ number_format($billableTotal, 2) }}</td>
            </tr>
            @if($dailyTotal > 0)
            <tr>
                <td>الرسوم اليومية</td>
                <td>{{ number_format($dailyTotal, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total-row">
                <td>الإجمالي الكلي</td>
                <td>{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Signature row ────────────────────────────────────────────── --}}
    <div class="signature-box">
        <div class="sig-cell">أعدّه</div>
        <div class="sig-cell" style="width:34%;"></div>
        <div class="sig-cell">اعتمده</div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-right">
            تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}
        </div>
        <div class="footer-left">
            {{ config('app.name') }} — فاتورة #{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
        </div>
    </div>

</div>
</body>
</html>
