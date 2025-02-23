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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // User who placed the order.
            $table->unsignedBigInteger('user_id');

            // Order type: 'buy' or 'sell'.
            $table->enum('type', ['buy', 'sell']);

            // Total quantity (in grams) for the order.
            $table->decimal('quantity', 8, 3);

            // Quantity remaining to be matched.
            $table->decimal('remaining_quantity', 8, 3);

            // Price per gram in Rial.
            $table->bigInteger('price');

            // Order status: open, completed, or cancelled.
            $table->enum('status', ['open', 'completed', 'cancelled'])->default('open');
            $table->timestamps();

            // Foreign key constraint (assumes users table exists).
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
