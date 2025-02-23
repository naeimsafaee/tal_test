<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * List all orders and trades for market overview.
     *
     */
    public function index()
    {
        // Paginate orders with related user data (10 per page).
        $orders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);


        return response()->json([
            'orders' => OrderResource::collection($orders),
            'orders_meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }


    /**
     * Place a new order (buy or sell).
     *
     */
    public function store(StoreOrderRequest $request)
    {
        // Retrieve the validated data from the StoreOrderRequest.
        $validatedData = $request->validated();
        $user = User::query()->find($validatedData['user_id']);

        if ($validatedData['type'] === 'sell' && $validatedData['quantity'] > $user->gold_balance) {
            return response()->json([
                'message' => __('order.insufficient_gold_balance')
            ], 400);
        }

        // Use a database transaction for atomicity.
        DB::beginTransaction();

        try {
            // Create a new order with the full quantity available.
            $order = Order::query()->create([
                'user_id' => $validatedData['user_id'], // will use auth guard and middleware in future
                'type' => $validatedData['type'],
                'quantity' => $validatedData['quantity'],
                'remaining_quantity' => $validatedData['quantity'],
                'price' => $validatedData['price'],
                'status' => 'open'
            ]);

            // update user's gold balance if it's a sell order.
            if ($validatedData['type'] === 'sell') {
                $user->gold_balance -= $validatedData['quantity'];
                $user->save();
            }

            // Attempt to match this order with any available opposite orders.
            $this->matchOrder($order);


            DB::commit();

            return response()->json([
                'message' => __('order.success_order_placed'),
                'order' => $order,
            ], 201);

        } catch (\Exception $e) {
            // Roll back in case of any errors.
            DB::rollBack();
            return response()->json([
                'message' => __('order.error_placing_order'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Cancel an open order.
     *
     * Only orders with status 'open' can be cancelled.
     *
     */
    public function cancel($orderId)
    {
        $order = Order::query()->findOrFail($orderId);

        // Check if the order is still open.
        if ($order->status !== 'open') {
            return response()->json([
                'message' => __('order.only_open_orders_can_be_cancelled')
            ], 400);
        }

        // Marks order as cancelled.
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'message' => __('order.order_cancelled_successfully'),
            'order' => $order,
        ]);
    }

    /**
     * Matching engine to match orders.
     *
     * When a new order is placed, this method attempts to match it with existing
     * opposite orders (i.e., if the new order is a buy, it matches with open sell orders)
     * having the same price, in a FIFO order.
     *
     */
    private function matchOrder(Order $order)
    {
        // Determine the opposite order type.
        $oppositeType = $order->type === 'buy' ? 'sell' : 'buy';

        // Iterate through matching orders.
        Order::query()
            ->where('type', $oppositeType)
            ->where('price', $order->price)
            ->where('status', 'open')
            ->orderBy('created_at', 'asc')
            ->chunk(100, function ($matchingOrders) use ($order) {
                // Iterate through each chunk of matching orders.
                foreach ($matchingOrders as $matchOrder) {
                    // Stop processing if the current order is fully executed.
                    if ($order->remaining_quantity <= 0) {
                        // Returning false stops further chunk processing.
                        return false;
                    }

                    // Determine the maximum quantity that can be traded.
                    $tradeQuantity = min($order->remaining_quantity, $matchOrder->remaining_quantity);

                    // Calculate commission based on trade quantity and trade value.
                    $commission = $this->calculateCommission($tradeQuantity, $order->price);

                    // Identify buy and sell orders for the trade.
                    if ($order->type === 'buy') {
                        $buyOrderId = $order->id;
                        $sellOrderId = $matchOrder->id;
                    } else {
                        $buyOrderId = $matchOrder->id;
                        $sellOrderId = $order->id;
                    }

                    // Record the trade.
                    Trade::query()->create([
                        'buy_order_id' => $buyOrderId,
                        'sell_order_id' => $sellOrderId,
                        'quantity' => $tradeQuantity,
                        'price' => $order->price, // Trade price (assumed same for both orders)
                        'commission' => $commission,
                    ]);

                    // Update the remaining quantities for both orders.
                    $order->remaining_quantity -= $tradeQuantity;
                    $matchOrder->remaining_quantity -= $tradeQuantity;

                    // Mark the matching order as completed if its remaining quantity is 0.
                    if ($matchOrder->remaining_quantity <= 0) {
                        $matchOrder->status = 'completed';
                    }
                    $matchOrder->save();

                    // Mark the current order as completed if its remaining quantity is 0.
                    if ($order->remaining_quantity <= 0) {
                        $order->status = 'completed';
                    }
                    $order->save();
                }
                return true;

            });
    }

    /**
     * Calculate commission for a trade based on quantity and price.
     *
     * Commission rules:
     * - Up to 1 gram: 2%
     * - From above 1 gram up to 10 grams: 1.5%
     * - Above 10 grams: 1%
     * Minimum commission: 500,000 Rial.
     * Maximum commission: 50,000,000 Rial.
     *
     * @param float $quantity Traded quantity in grams.
     * @param int $price Price per gram in Rial.
     * @return int Calculated commission (in Rial).
     */
    private function calculateCommission($quantity, $price)
    {
        // Calculate the total value of the trade.
        $tradeValue = $quantity * $price;

        // Determine the commission percentage based on trade quantity.
        if ($quantity <= 1) {
            $percentage = 2;
        } elseif ($quantity <= 10) {
            $percentage = 1.5;
        } else {
            $percentage = 1;
        }

        // Calculate commission as a percentage of the trade value.
        $commission = $tradeValue * ($percentage / 100);

        // Apply the minimum and maximum commission limits.
        $commission = max($commission, 500000);
        $commission = min($commission, 50000000);

        return (int)$commission;
    }
}
