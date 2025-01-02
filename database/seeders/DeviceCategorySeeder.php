<?php

namespace Database\Seeders;

use App\Models\DeviceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Phones',
            'Accessories'
        ];

        foreach ($categories as $category) {
            DeviceCategory::create([
                'name' => $category,
                'slug' => Str::slug($category)
            ]);
        }
    }
}
