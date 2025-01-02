<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'device_category_id',
        'device_subcategory_id',
        'name',
        'description',
        'image',
        'status'
    ];

    public function deviceCategory()
    {
        return $this->belongsTo(DeviceCategory::class);
    }

    public function deviceSubcategory()
    {
        return $this->belongsTo(DeviceSubcategory::class);
    }
}
