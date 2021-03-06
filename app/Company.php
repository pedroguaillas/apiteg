<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'ruc', 'company', 'economic_activity',
        'accounting', 'micro_business', 'retention_agent',
        'phone', 'logo_dir', 'cert_dir', 'pass_cert',
        'sign_valid_from', 'sign_valid_to',
        'enviroment_type', 'decimal',

        //Agregado las 2 columas el 25 de enero del 2022
        'rimpe'
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
