<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['sale_id', 'date', 'amount'];

    protected $connection = 'mysql';

    protected $table = 'collections';
}
