<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetentionShop extends Model
{
    protected $fillable = ['shop_id', 'serie', 'date'];

    public function retentionshopitems()
    {
        return $this->hasMany(RetentionShopItem::class);
    }
}
