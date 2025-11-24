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
        Schema::create('company_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('maker_checker_creating_updating')->default(false);
            $table->boolean('auto_request_financing')->nullable()->default(false);
            $table->enum('purchase_order_acceptance', ['auto', 'manual'])->nullable()->default('manual');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('footer', 100)->nullable();
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
        Schema::dropIfExists('company_invoice_settings');
    }
};
