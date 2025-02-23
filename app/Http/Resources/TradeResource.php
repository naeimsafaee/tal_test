<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'buy_order' => new OrderResource($this->buyOrder),
            'sell_order' => new OrderResource($this->sellOrder),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'commission' => $this->commission,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
