<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetentionItem extends Model
{
    protected $fillable = [
        'code', 'tax_code', 'base', 'porcentage', 'value', 'retention_id'
    ];

    public $timestamps = false;
}
