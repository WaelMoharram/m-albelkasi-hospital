<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    protected $fillable = ['name', 'contact_info'];

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }
}
