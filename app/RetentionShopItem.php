<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetentionShopItem extends Model
{
    protected $fillable = ['code', 'tax_code', 'base', 'porcentage', 'value', 'retention_shop_id'];
}
