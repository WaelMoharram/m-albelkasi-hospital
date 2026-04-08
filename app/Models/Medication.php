<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = ['name', 'unit', 'price', 'type'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
        }

        return $query;
    }
}
