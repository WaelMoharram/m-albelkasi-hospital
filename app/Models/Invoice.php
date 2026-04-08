<?php

namespace App\Models;

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
        $this->update(['total_amount' => $this->items()->sum('total')]);
    }
}
