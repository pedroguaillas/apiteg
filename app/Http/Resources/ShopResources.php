<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShopResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'atts' => [
                'date' => $this->date,
                'voucher_type' => $this->voucher_type,
                'serie' => $this->serie,
                'state_retencion' => $this->state_retencion,
                'total' => $this->total,
            ],
            'provider' => [
                'name' => $this->name,
            ]
        ];
    }
}
