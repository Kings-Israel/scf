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
        Schema::create('program_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_type_id')->references('id')->on('program_types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('abbrev')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_codes');
    }
};
