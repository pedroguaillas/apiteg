<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{

    protected $fillable = [
        'identification_type', 'identification_value', 'name', 'email', 'license_plate'
    ];
}
