<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            // References to the matching orders.
            $table->unsignedBigInteger('buy_order_id');
            $table->unsignedBigInteger('sell_order_id');

            // Quantity traded in grams.
            $table->decimal('quantity', 8, 3);

            // Price per gram (in Rial) for the trade.
            $table->bigInteger('price');

            // Commission applied for the trade (in Rial).
            $table->bigInteger('commission');
            $table->timestamps();

            // Foreign key constraints.
            $table->foreign('buy_order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('sell_order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
