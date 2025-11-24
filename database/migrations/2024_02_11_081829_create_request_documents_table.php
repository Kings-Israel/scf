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
        Schema::create('request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('pipeline_id')->nullable()->references('id')->on('pipelines')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->enum('status', ['pending', 'uploaded'])->default('pending');
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
        Schema::dropIfExists('request_documents');
    }
};
