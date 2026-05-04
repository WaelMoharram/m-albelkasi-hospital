<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends Model
{
    protected $fillable = [
        'patient_id',
        'admission_date',
        'discharge_date',
        'room',
        'ward',
        'referral_number',
        'referral_source',
        'status',
        'discharge_reason',
    ];

    protected function casts(): array
    {
        return [
            'admission_date'  => 'date',
            'discharge_date'  => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->whereHas('patient', fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
            );
        }

        return $query;
    }
}
