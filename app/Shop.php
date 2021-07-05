<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    //
    protected $fillable = [
        'serie', 'provider_id',
        'date', 'expiration_date',
        'no_iva', 'base0', 'base12',
        'iva', 'sub_total', 'discount',
        'total', 'voucher_type',
        'pay_method', 'notes', 'paid'
    ];

    public function shopitems()
    {
        return $this->hasMany(ShopItem::class);
    }

    protected $connection = 'mysql';

    protected $table = 'shops';
}
