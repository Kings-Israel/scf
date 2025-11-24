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
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pipeline_id');
            $table->string('initial_stage');
            $table->string('new_stage');
            $table->integer('period');
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamps();

            $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
