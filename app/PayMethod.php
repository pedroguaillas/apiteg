<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayMethod extends Model
{
    protected $fillable = [
        'vaucher_id', 'code', 'value', 'term', 'unit_time'
    ];

    // Required because not exits the column update_at
    public $timestamps = false;

    // public function voucher()
    // {
    //     return $this->belongsTo(Voucher::class, 'movement_id', 'vaucher_id');
    // }
}
