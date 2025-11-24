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
        Schema::create('program_company_roles', function (Blueprint $table) {
            $table->id();
            $table->unique(['program_id', 'company_id', 'role_id']);
            $table->foreignId('program_id')->references('id')->on('programs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('role_id')->references('id')->on('program_roles')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_company_roles');
    }
};
