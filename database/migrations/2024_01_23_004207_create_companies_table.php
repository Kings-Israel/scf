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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->nullable()->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('unique_identification_number')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('business_identification_number')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('business_segment')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('cust_ancode')->nullable();
            $table->string('logo')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('address')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable()->default('pending');
            $table->enum('status', ['active', 'inactive'])->nullable()->default('active');
            $table->string('relationship_manager_name')->nullable();
            $table->string('relationship_manager_email')->nullable();
            $table->string('relationship_manager_phone_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
