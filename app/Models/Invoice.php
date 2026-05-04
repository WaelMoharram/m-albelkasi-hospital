<?php

namespace App\Models;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'admission_id',
        'invoice_date',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function recalculateTotal(): void
    {
        $localDiscount    = (float) Setting::getValue('local_med_discount', 0)    / 100;
        $importedDiscount = (float) Setting::getValue('imported_med_discount', 0) / 100;

        $base     = $this->items()->whereNotIn('section', ['local_med', 'imported_med'])->sum('total');
        $localRaw = $this->items()->where('section', 'local_med')->sum('total');
        $impRaw   = $this->items()->where('section', 'imported_med')->sum('total');

        $total = $base
            + round($localRaw * (1 - $localDiscount), 2)
            + round($impRaw   * (1 - $importedDiscount), 2);

        $this->update(['total_amount' => $total]);
    }

    public function medicationDiscountedSubtotals(): array
    {
        $localDiscount    = (float) Setting::getValue('local_med_discount', 0)    / 100;
        $importedDiscount = (float) Setting::getValue('imported_med_discount', 0) / 100;

        $localRaw = (float) $this->items()->where('section', 'local_med')->sum('total');
        $impRaw   = (float) $this->items()->where('section', 'imported_med')->sum('total');

        return [
            'local_raw'           => $localRaw,
            'local_discount_pct'  => $localDiscount * 100,
            'local_after'         => round($localRaw * (1 - $localDiscount), 2),
            'imported_raw'        => $impRaw,
            'imported_discount_pct' => $importedDiscount * 100,
            'imported_after'      => round($impRaw * (1 - $importedDiscount), 2),
        ];
    }
}
