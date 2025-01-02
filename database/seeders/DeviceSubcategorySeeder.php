<?php

namespace Database\Seeders;

use App\Models\DeviceSubcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSubcategorySeeder extends Seeder
{
    public function run()
    {
        $subcategories = [
            // Phone subcategories
            [
                'device_category_id' => 1,
                'name' => 'iPhone',
                'slug' => Str::slug('iPhone')
            ],
            [
                'device_category_id' => 1,
                'name' => 'Android',
                'slug' => Str::slug('Android')
            ],
            // Accessories subcategories
            [
                'device_category_id' => 2,
                'name' => 'AirPods',
                'slug' => Str::slug('AirPods')
            ],
            [
                'device_category_id' => 2,
                'name' => 'Chargers',
                'slug' => Str::slug('Chargers')
            ],
            [
                'device_category_id' => 2,
                'name' => 'Cases',
                'slug' => Str::slug('Cases')
            ],
            [
                'device_category_id' => 2,
                'name' => 'Screen Protectors',
                'slug' => Str::slug('Screen Protectors')
            ],
            [
                'device_category_id' => 2,
                'name' => 'Power Banks',
                'slug' => Str::slug('Power Banks')
            ]
        ];

        foreach ($subcategories as $subcategory) {
            DeviceSubcategory::create($subcategory);
        }
    }
}
