<?php

namespace Database\Seeders;

use App\Models\InvoiceCategory;
use Illuminate\Database\Seeder;

class InvoiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'الإقامة',         'sort_order' => 1],
            ['name' => 'إشراف طبي',       'sort_order' => 2],
            ['name' => 'خدمات تمريضية',   'sort_order' => 3],
            ['name' => 'استخدام أجهزة',   'sort_order' => 4],
            ['name' => 'أكسجين',          'sort_order' => 5],
            ['name' => 'أشعة',            'sort_order' => 6],
            ['name' => 'أخرى',            'sort_order' => 7],
        ];

        foreach ($categories as $cat) {
            InvoiceCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
