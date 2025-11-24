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
        Schema::create('discount_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_discount_slab_id')->references('id')->on('payment_discount_slabs')->onDelete('cascade')->onUpdate('cascade');
            $table->string('from_day');
            $table->string('to_day');
            $table->string('discount_percentage');
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
        Schema::dropIfExists('discount_slabs');
    }
};
