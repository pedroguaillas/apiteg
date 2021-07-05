<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetentionSale extends Model
{
    protected $fillable = ['sale_id', 'serie', 'date'];

    public function retentionsaleitems()
    {
        return $this->hasMany(RetentionSaleItem::class);
    }
}
