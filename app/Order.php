<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'branch_id', 'date',
        'description', 'sub_total',
        'serie', 'customer_id',
        'doc_realeted', 'expiration_days',
        'no_iva', 'base0', 'base12',
        'iva', 'discount',
        'total', 'voucher_type',
        'paid',
        // Electronic
        'state', 'autorized',
        'authorization', 'iva_retention',
        'rent_retention', 'xml',
        'extra_detail',
        // Retencion
        'serie_retencion', 'date_retention',
        'authorization_retention'
    ];

    public function orderitems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
