<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'quantity',
        'price',
        'commission'
    ];

    /**
     * Get the buy order associated with the trade.
     */
    public function buyOrder()
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    /**
     * Get the sell order associated with the trade.
     */
    public function sellOrder()
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

}
