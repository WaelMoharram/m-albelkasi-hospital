<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

class UnitService
{
    public function all(): Collection
    {
        return Unit::orderBy('name')->get();
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit;
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
