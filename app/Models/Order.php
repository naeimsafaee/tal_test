<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'quantity',
        'remaining_quantity',
        'price',
        'status'
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship for trades where this order is the buy order.
     */
    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    /**
     * Relationship for trades where this order is the sell order.
     */
    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

}
