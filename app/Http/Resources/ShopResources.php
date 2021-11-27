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
                // Retención
                'serie_retencion' => $this->serie_retencion,
                'state_retencion' => $this->state_retencion,
                'xml_retention' => $this->xml_retention,
                'total' => $this->total,
                // Liquidación en compra
                'state' => $this->state,
                'xml' => $this->xml,
            ],
            'provider' => [
                'name' => $this->name,
            ]
        ];
    }
}
