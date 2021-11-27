<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    protected $fillable = [
        'branch_id', 'type_identification', 'identication',
        'name', 'email', 'license_plate'
    ];
}
