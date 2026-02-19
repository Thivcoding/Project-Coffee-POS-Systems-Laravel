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
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->unsignedBigInteger('cart_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->decimal('total_amount',12,2);
            $table->enum('status', ['pending','partial','paid','cancelled'])
                ->default('pending');
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();

            $table->foreign('cart_id')
                ->references('cart_id')->on('carts');

            $table->foreign('user_id')
                ->references('user_id')->on('users');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
