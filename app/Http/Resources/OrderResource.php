<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->user, //  wrap this in UserResource (currently doesn't have that)
            'type' => $this->type,
            'quantity' => $this->quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'price' => $this->price,
            'status' => $this->status,
            // Include buyTrades using TradeResource if the relationship is loaded.
            'buy_trades' => TradeResource::collection($this->buyTrades),
            // Include sellTrades using TradeResource if the relationship is loaded.
            'sell_trades' => TradeResource::collection($this->sellTrades),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
