<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];
    public function path()
    {
        return route('invoices.show', $this);
    }
    public function itemLines()
    {
        return $this->hasMany(InvoiceItemLine::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sales()
    {
        return $this->morphMany('App\Models\Sale', 'salable');
    }
    public function journalEntry()
    {
        return $this->morphOne('App\Models\JournalEntry', 'journalizable');
    }
    public function transaction()
    {
        return $this->morphOne('App\Models\Transaction', 'transactable');
    }
    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }
    public function delete()
    {
        $res=parent::delete();
        if ($res==true) {
            $relations = $this->sales;
            foreach ($relations as $relation) {
                $relation->delete();
            }
            if (!is_null($this->journalEntry)) {
                $this->journalEntry->delete();
            }
            if (!is_null($this->transaction)) {
                $this->transaction->delete();
            }
        }
    }
}
