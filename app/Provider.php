<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'branch_id', 'state', 'type_identification',
        'identication', 'name', 'address', 'phone',
        'email', 'accounting', 'discount'
    ];
}
