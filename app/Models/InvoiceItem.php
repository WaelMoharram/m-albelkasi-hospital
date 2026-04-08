<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'itemable_id',
        'itemable_type',
        'qty',
        'unit_price',
        'total',
        'section',
        'service_date',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'   => 'decimal:2',
            'total'        => 'decimal:2',
            'service_date' => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
