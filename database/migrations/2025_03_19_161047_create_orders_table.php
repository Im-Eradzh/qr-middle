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
            $table->string('merchantId')->nullable();
            $table->string('orderId')->nullable();            
            $table->decimal('orderAmount', 15, 2);
            $table->string('channelType')->nullable();
            $table->string('notifyUrl')->nullable();
            $table->string('sign')->nullable();
            $table->string('returnUrl')->nullable();
            $table->string('transaction_refno')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->boolean('notified')->default(false);
            $table->string('token');
            $table->timestamps();
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
