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
      Schema::create('company_relationship_managers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
        $table->string('name');
        $table->string('email');
        $table->string('phone_number')->nullable();
        $table->timestamps();
      });

      Schema::table('companies', function (Blueprint $table) {
          $table->dropColumn('relationship_manager_name');
          $table->dropColumn('relationship_manager_email');
          $table->dropColumn('relationship_manager_phone_number');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('company_relationship_managers');
    }
};
