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
        Schema::create('bank_product_repayment_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('product_type_id')->references('id')->on('program_types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('particulars');
            $table->enum('discount_indicator', ['discount bearing', 'non discount bearing'])->nullable()->default('discount bearing');
            $table->string('premature_priority')->nullable()->default('0');
            $table->string('npa_priority')->nullable()->default('0');
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
        Schema::dropIfExists('bank_product_repayment_priorities');
    }
};
