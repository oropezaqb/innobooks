<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];
    public function material()
    {
        return $this->belongsTo('App\Material');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }
    public function purchasable()
    {
        return $this->morphTo();
    }
}
