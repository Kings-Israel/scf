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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->references('id')->on('companies')->onUpdate('cascade')->onUpdate('cascade');
            $table->string('purchase_order_number');
            $table->string('currency')->nullable();
            $table->date('duration_from');
            $table->date('duration_to');
            $table->date('delivery_date');
            $table->string('delivery_address');
            $table->integer('invoice_payment_terms');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
