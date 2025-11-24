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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->references('id')->on('programs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('dealer_id')->nullable()->references('id')->on('companies')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('purchase_order_id')->nullable()->references('id')->on('purchase_orders')->onUpdate('cascade')->onDelete('set null');
            $table->string('invoice_number');
            $table->string('payment_od_account')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency')->nullable();
            $table->string('remarks')->nullable();
            $table->string('attachment')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->enum('status', ['created', 'pending', 'submitted', 'approved', 'disbursed', 'denied'])->nullable()->default('created');
            $table->enum('financing_status', ['pending', 'financed', 'closed'])->nullable()->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
