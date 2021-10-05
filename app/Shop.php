<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'branch_id', 'date', 'description', 'sub_total',
        'serie', 'provider_id', 'doc_realeted',
        'expiration_days', 'no_iva', 'base0',
        'base12', 'iva', 'discount', 'ice', 'total',
        'voucher_type', 'paid',

        // Electrónico
        'state', 'autorized', 'authorization',
        'iva_retention', 'rent_retention', 'xml',
        'extra_detail',

        // Retención
        'serie_retencion', 'date_retention',

        // Retención electrónica
        'state_retencion', 'autorized_retention',
        'authorization_retention', 'xml_retention',
        'extra_detail_retention'
    ];

    public function shopitems()
    {
        return $this->hasMany(ShopItem::class);
    }

    public function shopretentionitems()
    {
        return $this->hasMany(ShopRetentionItem::class);
    }
}
