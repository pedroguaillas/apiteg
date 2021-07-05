<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovementItem extends Model
{
    protected $fillable = [
        'movement_id', 'product_id', 'price', 'quantity', 'discount'
    ];
}
