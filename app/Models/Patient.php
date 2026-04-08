<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name',
        'national_id',
        'dob',
        'gender',
        'insurance_company_id',
        'policy_number',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('policy_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
