<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = ['identification_value', 'identification_type', 'name', 'type', 'required_account','direction','phone','mail'];
}