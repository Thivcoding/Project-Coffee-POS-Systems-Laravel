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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id('cart_item_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('size_id'); // ✅ add size_id
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);      // price of selected size
            $table->decimal('subtotal', 10, 2);   // quantity * price
            $table->timestamps();

            // Foreign keys
            $table->foreign('cart_id')
                ->references('cart_id')->on('carts')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('product_id')->on('products')
                ->onDelete('cascade');

            $table->foreign('size_id')
                ->references('id')->on('sizes')
                ->onDelete('cascade');

            // ✅ prevent duplicate product with same size in same cart
            $table->unique(['cart_id', 'product_id', 'size_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};

