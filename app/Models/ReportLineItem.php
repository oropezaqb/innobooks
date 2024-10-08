<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLineItem extends Model
{
    protected $guarded = [];
    public function path()
    {
        return route('report_line_items.show', $this);
    }
    public function posting()
    {
        return $this->belongsTo(Posting::class);
    }
}
