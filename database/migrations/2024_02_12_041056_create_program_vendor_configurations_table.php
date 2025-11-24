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
        Schema::create('program_vendor_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->references('id')->on('programs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('payment_account_number')->nullable();
            $table->string('sanctioned_limit')->nullable();
            $table->date('limit_approved_date')->nullable();
            $table->date('limit_expiry_date')->nullable();
            $table->date('limit_review_date')->nullable();
            $table->bigInteger('drawing_power')->nullable();
            $table->boolean('request_auto_finance')->default(false);
            $table->boolean('auto_approve_finance')->default(false);
            $table->integer('eligibility')->nullable();
            $table->integer('invoice_margin')->nullable();
            $table->string('schema_code')->nullable();
            $table->text('product_description')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('classification')->nullable();
            $table->string('tds')->nullable();
            $table->enum('status', ['active', 'inactive'])->nullable()->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progrm_vendor_configurations');
    }
};
