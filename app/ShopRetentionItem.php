<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopRetentionItem extends Model
{
    protected $fillable = [
        'code', 'tax_code', 'base',
        'porcentage', 'value', 'shop_id'
    ];

    /**
     * Get the post that owns the comment.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the post that owns the comment.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
