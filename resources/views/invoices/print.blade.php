<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة #{{ $invoice->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5pt;
            color: #111;
            background: #fff;
            direction: rtl;
        }

        .page { width: 100%; padding: 12mm 14mm 10mm 14mm; }

        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2pt solid #1a3c6e;
            padding-bottom: 8pt;
            margin-bottom: 10pt;
        }
        .header-right { display: table-cell; vertical-align: top; width: 60%; }
        .header-left  { display: table-cell; vertical-align: top; width: 40%; text-align: left; }
        .logo-wrap img { max-height: 55pt; max-width: 120pt; margin-bottom: 3pt; }
        .hosp-name  { font-size: 14pt; font-weight: bold; color: #1a3c6e; }
        .hosp-meta  { font-size: 7.5pt; color: #555; margin-top: 2pt; line-height: 1.5; }
        .inv-label  { font-size: 22pt; font-weight: bold; color: #1a3c6e; text-align: left; }
        .inv-meta   { font-size: 8pt; color: #555; text-align: left; margin-top: 3pt; line-height: 1.7; }
        .badge-final { display:inline-block; background:#198754; color:#fff; font-size:7pt; font-weight:bold; padding:1pt 5pt; border-radius:2pt; }
        .badge-draft  { display:inline-block; background:#ffc107; color:#000; font-size:7pt; font-weight:bold; padding:1pt 5pt; border-radius:2pt; }

        /* ── Patient info ── */
        .patient-box {
            border: 0.5pt solid #b0b8c8;
            border-radius: 3pt;
            margin-bottom: 10pt;
            display: table;
            width: 100%;
        }
        .patient-row  { display: table-row; }
        .patient-cell {
            display: table-cell;
            padding: 5pt 8pt;
            border-left: 0.5pt solid #b0b8c8;
            vertical-align: top;
        }
        .patient-cell:last-child { border-left: none; }
        .p-label { font-size: 7pt; color: #777; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
        .p-value { font-size: 9.5pt; font-weight: bold; margin-top: 2pt; }
        .p-sub   { font-size: 7.5pt; color: #555; margin-top: 1pt; }

        /* ── Main items table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }

        .items-table thead th {
            background: #1a3c6e;
            color: #fff;
            font-size: 8pt;
            font-weight: bold;
            padding: 4pt 5pt;
            text-align: right;
            border: 0.25pt solid #1a3c6e;
        }

        .items-table tbody td {
            padding: 3pt 5pt;
            font-size: 8.5pt;
            border: 0.25pt solid #d0d8e4;
            text-align: right;
            vertical-align: middle;
        }

        .cat-row td {
            background: #e8edf5;
            font-weight: bold;
            font-size: 8.5pt;
            color: #1a3c6e;
            padding: 3pt 5pt;
            border: 0.25pt solid #b0b8c8;
        }

        .row-no-col { width: 22pt; text-align: center; }
        .code-col   { width: 55pt; }
        .qty-col    { width: 30pt; text-align: center; }
        .price-col  { width: 55pt; text-align: center; }
        .total-col  { width: 60pt; text-align: center; font-weight: bold; }

        .subtotal-row td {
            background: #f2f5fb;
            font-size: 8pt;
            font-weight: bold;
            border-top: 0.5pt solid #1a3c6e;
            padding: 3pt 5pt;
        }

        /* ── Summary ── */
        .summary-wrap  { display: table; width: 100%; margin-top: 4pt; }
        .summary-left  { display: table-cell; vertical-align: top; width: 55%; padding-left: 8pt; }
        .summary-right { display: table-cell; vertical-align: top; width: 45%; }

        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td {
            padding: 4pt 7pt;
            font-size: 9pt;
            border: 0.25pt solid #b0b8c8;
            text-align: right;
        }
        .sum-no    { width: 22pt; text-align: center; background: #e8edf5; font-weight: bold; color: #1a3c6e; }
        .sum-label { font-size: 8.5pt; }
        .sum-amt   { width: 80pt; text-align: center; font-weight: bold; }
        .sum-grand td { background: #1a3c6e; color: #fff; font-size: 10.5pt; font-weight: bold; padding: 5pt 7pt; }
        .disc-note { font-size: 7pt; color: #888; }

        /* ── Signatures ── */
        .sig-wrap { display: table; width: 100%; margin-top: 20pt; }
        .sig-cell { display: table-cell; width: 33%; text-align: center; }
        .sig-line {
            border-top: 0.5pt solid #555;
            padding-top: 4pt;
            font-size: 8pt;
            color: #444;
            margin: 0 10pt;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 14pt;
            border-top: 0.5pt solid #b0b8c8;
            padding-top: 5pt;
            display: table;
            width: 100%;
        }
        .footer-right { display: table-cell; font-size: 7.5pt; color: #666; }
        .footer-left  { display: table-cell; text-align: left; font-size: 7.5pt; color: #666; }

        .watermark {
            position: fixed; top: 40%; left: 10%;
            font-size: 70pt; font-weight: bold;
            color: rgba(255, 193, 7, 0.12);
            transform: rotate(-35deg);
            z-index: -1;
        }
    </style>
</head>
<body>
@php
    use App\Models\Setting;
    use Carbon\Carbon;

    $admission  = $invoice->admission;
    $patient    = $admission->patient;

    $hospName        = Setting::getValue('hospital_name', config('app.name'));
    $hospLogo        = Setting::getValue('hospital_logo');
    $hospAddress     = Setting::getValue('hospital_address');
    $hospPhones      = Setting::getValue('hospital_phones');
    $hospPoBox       = Setting::getValue('hospital_po_box');
    $hospCommReg     = Setting::getValue('hospital_commercial_reg');
    $footerNote      = Setting::getValue('invoice_footer_note');
    $preparedBy      = Setting::getValue('invoice_prepared_by', 'أعدّه');
    $approvedBy      = Setting::getValue('invoice_approved_by', 'مدير المستشفى');
    $localDisc       = (float) Setting::getValue('local_med_discount', 0);
    $importedDisc    = (float) Setting::getValue('imported_med_discount', 0);

    $admDate  = Carbon::parse($admission->admission_date);
    $disDate  = $admission->discharge_date ? Carbon::parse($admission->discharge_date) : Carbon::today();
    $days     = $admDate->diffInDays($disDate) + 1;

    $serviceItems = $invoice->items->where('itemable_type', \App\Models\Service::class);
    $medItems     = $invoice->items->where('itemable_type', \App\Models\Medication::class);

    // Group services by invoice_category (sorted)
    $categoryGroups = collect();
    foreach ($serviceItems as $item) {
        $svc = $item->itemable;
        if (! $svc) continue;
        $cat = $svc->invoiceCategory ?? null;
        if (! $cat) continue;
        $key = $cat->id;
        if (! $categoryGroups->has($key)) {
            $categoryGroups->put($key, [
                'name'       => $cat->name,
                'sort_order' => $cat->sort_order,
                'items'      => collect(),
            ]);
        }
        $categoryGroups[$key]['items']->push($item);
    }
    $categoryGroups = $categoryGroups->sortBy('sort_order');

    $labItems    = $serviceItems->filter(fn($i) => $i->section === 'lab');
    $labTotal    = $labItems->sum('total');

    $accommodationTotal = $categoryGroups->sum(fn($g) => $g['items']->sum('total'));

    $localRaw    = $medItems->filter(fn($i) => $i->section === 'local_med')->sum('total');
    $importedRaw = $medItems->filter(fn($i) => $i->section === 'imported_med')->sum('total');
    $localAfter  = round($localRaw    * (1 - $localDisc    / 100), 2);
    $importAfter = round($importedRaw * (1 - $importedDisc / 100), 2);
    $grandTotal  = (float) $invoice->total_amount;

    // Logo as base64 for dompdf
    $logoBase64 = null;
    if ($hospLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($hospLogo)) {
        $logoPath   = \Illuminate\Support\Facades\Storage::disk('public')->path($hospLogo);
        $mime       = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp

@if($invoice->status === 'draft')
<div class="watermark">مسودة</div>
@endif

<div class="page">

{{-- ── HEADER ────────────────────────────────────────────────────── --}}
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
        <div class="inv-label">فاتورة</div>
        <div class="inv-meta">
            رقم: {{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}<br>
            التاريخ: {{ $invoice->invoice_date->format('d/m/Y') }}<br>
            <span class="{{ $invoice->status === 'final' ? 'badge-final' : 'badge-draft' }}">
                {{ $invoice->status === 'final' ? 'نهائي' : 'مسودة' }}
            </span>
        </div>
    </div>
</div>

{{-- ── PATIENT INFO ────────────────────────────────────────────── --}}
<div class="patient-box">
    <div class="patient-row">
        <div class="patient-cell" style="width:35%;">
            <div class="p-label">الاسم</div>
            <div class="p-value">{{ $patient->name }}</div>
            <div class="p-sub">{{ $patient->insuranceCompany->name ?? '—' }}</div>
            @if($admission->referral_number)<div class="p-sub">رقم التحويل: {{ $admission->referral_number }}</div>@endif
        </div>
        <div class="patient-cell" style="width:20%;">
            <div class="p-label">تاريخ الدخول</div>
            <div class="p-value">{{ $admDate->format('d/m/Y') }}</div>
        </div>
        <div class="patient-cell" style="width:20%;">
            <div class="p-label">تاريخ الخروج</div>
            <div class="p-value">{{ $admission->discharge_date ? $disDate->format('d/m/Y') : '—' }}</div>
        </div>
        <div class="patient-cell" style="width:12%;">
            <div class="p-label">مدة الإقامة</div>
            <div class="p-value">{{ $days }} يوم</div>
        </div>
        <div class="patient-cell" style="width:13%;">
            <div class="p-label">القسم</div>
            <div class="p-value" style="font-size:8.5pt;">{{ $admission->ward ?? '—' }}</div>
            <div class="p-sub">غرفة {{ $admission->room ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- ── MAIN ITEMS TABLE ────────────────────────────────────────── --}}
<table class="items-table">
    <thead>
        <tr>
            <th class="row-no-col">م</th>
            <th>بيان</th>
            <th class="qty-col">عدد</th>
            <th class="price-col">سعر الوحدة<br><span style="font-size:6pt;font-weight:normal;">ج.م</span></th>
            <th class="total-col">الإجمالي<br><span style="font-size:6pt;font-weight:normal;">ج.م</span></th>
            <th class="code-col">ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        @php $rowNo = 1; @endphp

        @foreach($categoryGroups as $group)
        <tr class="cat-row">
            <td class="row-no-col">{{ $rowNo }}</td>
            <td colspan="4">{{ $group['name'] }}</td>
            <td></td>
        </tr>

        @foreach($group['items'] as $item)
        <tr>
            <td class="row-no-col"></td>
            <td>{{ $item->itemable->name ?? '—' }}</td>
            <td class="qty-col">{{ $item->qty }}</td>
            <td class="price-col">{{ number_format($item->unit_price, 2) }}</td>
            <td class="total-col">{{ number_format($item->total, 2) }}</td>
            <td class="code-col" style="font-size:7.5pt; color:#777;">
                @if($item->service_date){{ \Carbon\Carbon::parse($item->service_date)->format('d/m') }}@endif
            </td>
        </tr>
        @endforeach

        <tr class="subtotal-row">
            <td colspan="3" style="text-align:right; color:#1a3c6e;">إجمالي {{ $group['name'] }}</td>
            <td class="total-col" style="border-left:none;">{{ number_format($group['items']->sum('total'), 2) }}</td>
            <td colspan="2" style="background:#f2f5fb;"></td>
        </tr>
        @php $rowNo++; @endphp
        @endforeach

        @if($categoryGroups->isEmpty())
        <tr>
            <td colspan="6" style="text-align:center; color:#aaa; padding:10pt; font-style:italic;">لا توجد بنود خدمية</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- ── SUMMARY ─────────────────────────────────────────────────── --}}
<div class="summary-wrap">

    {{-- Lab items detail (left side) --}}
    <div class="summary-left">
        @if($labItems->count())
        <table class="items-table" style="margin-bottom:0;">
            <thead>
                <tr><th colspan="4" style="background:#0d6efd;">تفاصيل التحاليل</th></tr>
                <tr>
                    <th>الاسم</th>
                    <th class="qty-col">عدد</th>
                    <th class="price-col">سعر</th>
                    <th class="total-col">إجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labItems as $item)
                <tr>
                    <td>{{ $item->itemable->name ?? '—' }}</td>
                    <td class="qty-col">{{ $item->qty }}</td>
                    <td class="price-col">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="total-col">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Summary totals (right side) --}}
    <div class="summary-right">
        <table class="summary-table">
            <tr>
                <td class="sum-no">8</td>
                <td class="sum-label">إجمالي الإقامة</td>
                <td class="sum-amt">{{ number_format($accommodationTotal, 2) }}</td>
            </tr>
            @if($labTotal > 0)
            <tr>
                <td class="sum-no">9</td>
                <td class="sum-label">تحاليل</td>
                <td class="sum-amt">{{ number_format($labTotal, 2) }}</td>
            </tr>
            @endif
            @if($localRaw > 0)
            <tr>
                <td class="sum-no">10</td>
                <td class="sum-label">
                    أدوية محلية
                    @if($localDisc > 0)<br><span class="disc-note">بعد خصم {{ $localDisc }}%</span>@endif
                </td>
                <td class="sum-amt">{{ number_format($localAfter, 2) }}</td>
            </tr>
            @endif
            @if($importedRaw > 0)
            <tr>
                <td class="sum-no">11</td>
                <td class="sum-label">
                    أدوية مستوردة
                    @if($importedDisc > 0)<br><span class="disc-note">بعد خصم {{ $importedDisc }}%</span>@endif
                </td>
                <td class="sum-amt">{{ number_format($importAfter, 2) }}</td>
            </tr>
            @endif
            <tr class="sum-grand">
                <td class="sum-no" style="background:#0f2a4e; color:#fff;">13</td>
                <td class="sum-label">الإجمالي</td>
                <td class="sum-amt">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
    </div>

</div>

{{-- ── SIGNATURES ──────────────────────────────────────────────── --}}
<div class="sig-wrap">
    <div class="sig-cell"><div class="sig-line">{{ $preparedBy }}</div></div>
    <div class="sig-cell"></div>
    <div class="sig-cell"><div class="sig-line">{{ $approvedBy }}</div></div>
</div>

{{-- ── FOOTER ──────────────────────────────────────────────────── --}}
<div class="footer">
    <div class="footer-right">
        @if($footerNote){{ $footerNote }}<br>@endif
        @if($hospAddress){{ $hospAddress }}&nbsp;@endif
        @if($hospPhones) — {{ $hospPhones }}@endif
    </div>
    <div class="footer-left">
        تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}<br>
        فاتورة رقم #{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
    </div>
</div>

</div>
</body>
</html>
