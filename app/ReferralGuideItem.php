<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferralGuideItem extends Model
{
    protected $fillable = [
        'referral_guide_id', 'product_id',
        'quantity',
    ];

    public function referralguide()
    {
        return $this->belongsTo(ReferralGuide::class);
    }
}
