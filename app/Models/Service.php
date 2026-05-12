<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = ['name', 'code', 'price', 'category', 'invoice_category_id', 'is_daily', 'daily_qty', 'is_once'];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'is_daily'  => 'boolean',
            'daily_qty' => 'integer',
            'is_once'   => 'boolean',
        ];
    }

    public function invoiceCategory(): BelongsTo
    {
        return $this->belongsTo(InvoiceCategory::class);
    }

    /**
     * Services that get auto-added when this service is added to an invoice.
     */
    public function triggers(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_triggers',
            'service_id',
            'triggered_service_id'
        );
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            );
        }

        return $query;
    }
}
