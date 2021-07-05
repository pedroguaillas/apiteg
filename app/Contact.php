<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'state', 'special', 'identication_card', 'ruc',
        'company', 'name', 'address', 'phone', 'email', 'accounting',
        'receive_account_id', 'discount', 'pay_account_id', 'rent_retention',
        'iva_retention'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
