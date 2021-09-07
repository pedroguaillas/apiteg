<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'code', 'tax_code',
        'base', 'porcentage',
        'value', 'order_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
