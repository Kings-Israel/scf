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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('program_type_id')->references('id')->on('program_types')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('program_code_id')->nullable()->references('id')->on('program_codes')->onDelete('set null')->onUpdate('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->bigInteger('eligibility')->nullable();
            $table->bigInteger('invoice_margin')->nullable();
            $table->bigInteger('program_limit')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('limit_expiry_date')->nullable();
            $table->bigInteger('max_limit_per_account')->nullable();
            $table->string('collection_account')->nullable();
            $table->boolean('request_auto_finance')->default(false);
            $table->string('stale_invoice_period')->nullable();
            $table->integer('min_financing_days')->nullable();
            $table->integer('max_financing_days')->nullable();
            $table->string('segment')->nullable();
            $table->boolean('auto_debit_anchor_financed_invoices')->default(false);
            $table->boolean('auto_debit_anchor_non_financed_invoices')->default(false);
            $table->boolean('anchor_can_change_due_date')->default(true);
            $table->integer('max_days_due_date_extension')->nullable();
            $table->integer('days_limit_for_due_date_change')->nullable();
            $table->integer('default_payment_terms')->nullable();
            $table->boolean('anchor_can_change_payment_term')->default(false);
            $table->string('repayment_appropriation')->nullable();
            $table->boolean('mandatory_invoice_attachment')->default(false);
            $table->string('partner')->nullable();
            $table->string('recourse')->nullable();
            $table->string('due_date_calculated_from')->nullable();
            $table->string('noa')->nullable();
            $table->enum('account_status', ['pending', 'active', 'suspended'])->nullable()->default('pending');
            $table->string('board_resolution_attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
