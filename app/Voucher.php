<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'movement_id', 'serie', 'contact_id', 'doc_realeted',
        'expiration_days', 'no_iva', 'base0', 'base12',
        'iva', 'sub_total', 'discount', 'total',
        'voucher_type', 'pay_method', 'paid'
        //date, description .... In movement.
        //type (1-ingreso, 2-salida)
    ];

    //Problem ORM
    protected $primaryKey = 'movement_id';

    /**
     * Get the Movement record associated with the Movement.
     */
    public function movement()
    {
        return $this->hasOne(Movement::class);
    }

    /**
     * Get the Retention record associated with the Retention.
     */
    public function retention()
    {
        return $this->belongsTo(Retention::class);
    }

    /**
     * Get the PayMethod record associated with the PayMethod.
     */
    public function paymethods()
    {
        return $this->hasMany(PayMethod::class, 'vaucher_id', 'movement_id');
    }
}
