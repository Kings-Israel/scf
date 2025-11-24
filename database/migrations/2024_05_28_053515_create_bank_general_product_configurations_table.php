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
        Schema::create('bank_general_product_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('product_type_id')->references('id')->on('program_types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('value')->nullable();
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
        Schema::dropIfExists('bank_general_product_configurations');
    }
};
