<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_products_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('product_type_id')->references('id')->on('program_types')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('product_code_id')->nullable()->references('id')->on('program_codes')->onDelete('cascade')->onUpdate('cascade');
            $table->string('section')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('value')->nullable();
            $table->boolean('branch_specific')->nullable()->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_products_configurations');
    }
};
