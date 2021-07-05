<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MovementResources extends JsonResource
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
                'branch_id' => $this->branch_id,
                'date' => $this->date,
                'type' => $this->type,
                'description' => $this->description,
                'seat_generate' => $this->seat_generate,
                'sub_total' => $this->sub_total,
            ]
        ];
    }
}
