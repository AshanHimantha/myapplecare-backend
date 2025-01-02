<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSubcategory extends Model
{
    protected $fillable = [
        'device_category_id',
        'name',
        'slug'
    ];

    public function deviceCategory()
    {
        return $this->belongsTo(DeviceCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
