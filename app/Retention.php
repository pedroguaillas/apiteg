<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Retention extends Model
{
    protected $fillable = ['vaucher_id', 'serie', 'date'];

    public function voucher()
    {
        return $this->hasOne(Voucher::class);
    }

    public function retentionitems()
    {
        return $this->hasMany(RetentionItem::class, 'retention_id', 'vaucher_id');
    }
}
