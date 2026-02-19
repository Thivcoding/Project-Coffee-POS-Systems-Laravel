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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('sale_id');
            $table->enum('method', ['cash', 'card', 'bakong'])->default('cash');
            $table->enum('status', ['pending','paid','failed','cancelled'])->default('pending');
            $table->decimal('amount', 12, 2);  
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('KHR');
            $table->text('qr_string')->nullable();
            $table->string('bakong_txn_id')->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();

            // foreign key
            $table->foreign('sale_id')
                ->references('sale_id')->on('sales')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
