<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    protected $fillable = [
        'branch_id', 'date', 'type', 'description', 'seat_generate', 'sub_total'
    ];

    function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    function movementitems()
    {
        return $this->hasMany(MovementItem::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
