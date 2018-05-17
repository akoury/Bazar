<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Value extends Model
{
    protected $guarded = [];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }
}
