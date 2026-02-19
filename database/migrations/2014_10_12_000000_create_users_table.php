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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); // primary key
            $table->string('username'); // ឈ្មោះប្រើ
            $table->string('email')->unique(); // អ៊ីមែល
            $table->timestamp('email_verified_at')->nullable(); // email verification
            $table->string('password'); // ពាក្យសម្ងាត់
            $table->enum('role', ['admin', 'cashier' , 'customer'])->default('customer'); // តួនាទី
            $table->enum('status', ['active', 'inactive'])->default('active'); // ស្ថានភាព
            $table->string('phone')->nullable(); // លេខទូរស័ព្ទ
            $table->string('address')->nullable(); // អាសយដ្ឋាន
            $table->text('notes')->nullable(); // កំណត់សំគាល់
            $table->rememberToken(); // សម្រាប់ login remember
            $table->timestamps(); // created_at, updated_at
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
