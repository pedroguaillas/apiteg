<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetentionSaleItem extends Model
{
    protected $fillable = ['code', 'tax_code', 'base','porcentage', 'value', 'retention_sale_id'];
}
