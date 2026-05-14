<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Medication extends Model
{
    protected $fillable = ['name', 'code', 'unit', 'price', 'type', 'daily_qty'];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'daily_qty' => 'integer',
        ];
    }

    public function triggeredServices(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Service::class,
            'medication_service_triggers',
            'medication_id',
            'service_id'
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
