<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setValue('local_med_discount', '15');
        Setting::setValue('imported_med_discount', '7');
    }
}
