<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCategory extends Model
{
    protected $fillable = ['name', 'slug'];

    public function deviceSubcategories()
    {
        return $this->hasMany(DeviceSubcategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
