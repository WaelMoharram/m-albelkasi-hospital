<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'price', 'category'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }
}
