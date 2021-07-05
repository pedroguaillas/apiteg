<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['serie', 'customer_id', 'date', 'expiration_date', 'sub_total', 'iva', 'no_iva', 'base12', 'base0', 'discount', 'total', 'type_voucher', 'method_pay', 'notes', 'paid'];

    /**
     * Get the post that owns the comment.
     */

    public function accountentries()
    {
        return $this->hasMany(AccountEntry::class);
    }

    public function saleitems()
    {
        //return $this->belongsToMany('App\SaleItem');
        return $this->hasMany(SaleItem::class);
    }

    protected $connection = 'mysql';

    protected $table = 'sales';
}
