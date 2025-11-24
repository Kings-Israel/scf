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
        Schema::create('program_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->references('id')->on('programs')->onDelete('cascade')->onUpdate('cascade');
            $table->string('benchmark_title')->nullable();
            $table->bigInteger('benchmark_rate')->nullable();
            $table->string('reset_frequency')->nullable();
            $table->string('days_frequency_days')->nullable();
            $table->bigInteger('business_strategy_spread')->nullable();
            $table->bigInteger('credit_spread')->nullable();
            $table->bigInteger('total_spread')->nullable();
            $table->bigInteger('total_roi')->nullable();
            $table->double('tax_on_discount')->nullable()->default(0);
            $table->bigInteger('anchor_discount_bearing')->nullable();
            $table->bigInteger('vendor_discount_bearing')->nullable();
            $table->string('discount_type')->nullable();
            $table->bigInteger('penal_discount_on_principle')->nullable();
            $table->string('anchor_fee_recovery')->nullable();
            $table->integer('grace_period')->nullable();
            $table->integer('grace_period_discount')->nullable();
            $table->string('maturity_handling_on_holidays')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_discounts');
    }
};
