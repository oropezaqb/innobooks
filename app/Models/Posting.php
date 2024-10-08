<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posting extends Model
{
    protected $guarded = [];
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
    public function account()
    {
        return $this->hasOne(Account::class);
    }
    public function subsidiaryLedger()
    {
        return $this->hasOne(SubsidiaryLedger::class);
    }
    public function reportLineItem()
    {
        return $this->hasOne(ReportLineItem::class);
    }
}
