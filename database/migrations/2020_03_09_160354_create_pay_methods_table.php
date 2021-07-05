<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_methods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('vaucher_id')->unsigned();
            $table->char('code', 2);    //Code method of pay
            $table->decimal('value', 14, 6);
            $table->integer('term')->nullable();    //Plazo
            $table->string('unit_time', 5)->nullable();

            $table->foreign('vaucher_id')->references('movement_id')->on('vouchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pay_methods');
    }
}
