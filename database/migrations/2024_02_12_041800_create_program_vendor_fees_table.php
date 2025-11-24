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
        Schema::create('program_vendor_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->references('id')->on('programs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('fee_name')->nullable();
            $table->enum('type', ['percentage', 'amount'])->nullable()->default('percentage');
            $table->bigInteger('value')->nullable();
            $table->bigInteger('anchor_bearing_discount')->nullable();
            $table->bigInteger('vendor_bearing_discount')->nullable();
            $table->string('taxes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progrm_vendor_fees');
    }
};
