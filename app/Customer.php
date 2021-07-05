<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{

    protected $fillable = ['identification_type', 'identification_value', 'name', 'direction', 'phone', 'email', 'type_tax_payer'];
}
