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
         Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('size_id');

            $table->decimal('price',10,2);
            $table->integer('stock_qty')->default(0);

            $table->timestamps();

            $table->foreign('product_id')
                  ->references('product_id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('size_id')
                  ->references('id')
                  ->on('sizes')
                  ->onDelete('cascade');

            $table->unique(['product_id','size_id']); // prevent duplicate size
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};
