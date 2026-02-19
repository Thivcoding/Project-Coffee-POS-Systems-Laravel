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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id('sale_detail_id');

            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('size_id');

            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();

            $table->foreign('sale_id')
                ->references('sale_id')
                ->on('sales')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products');

            $table->foreign('size_id')
                ->references('id')    
                ->on('sizes')
                ->onDelete('cascade');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
