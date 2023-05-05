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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->string("total")->nullable();
            $table->dateTime("start_date")->nullable();
            $table->string("number_of_months")->nullable();
            $table->string("total_of_months")->nullable();
            $table->string("paid")->nullable();
            $table->string("paidCount")->nullable();
            $table->string("remaining")->nullable();
            $table->integer("transaction_id");
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
        Schema::dropIfExists('installments');
    }
};
