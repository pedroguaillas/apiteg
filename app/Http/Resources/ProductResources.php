<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResources extends JsonResource
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
                'code' => $this->code,
                'type_product' => $this->type_product,
                'name' => $this->name,
                'price1' => $this->price1,
                'price2' => $this->price2,
                // 'price3' => $this->price3,
                'iva' => $this->iva,
                'ice' => $this->ice,
                'irbpnr' => $this->irbpnr,
                // 'entry_account_id' => $this->entry_account_id,
                // 'active_account_id' => $this->active_account_id,
                // 'inventory_account_id' => $this->inventory_account_id,
                // 'stock' => $this->stock,
            ],
            // 'category' => [
            //     'category_id' => $this->category_id,
            //     'category' => $this->category,
            // ],
            // 'unity' => [
            //     'unity_id' => $this->unity_id,
            //     'unity' => $this->unity
            // ]
        ];
    }
}
