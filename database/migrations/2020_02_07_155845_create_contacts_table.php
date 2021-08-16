<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id');
            $table->integer('state');   //1-activo/2-inactivo
            // $table->integer('type');    //1-natural/2-juridica ---Calculable
            $table->boolean('special'); //Contribution special (company)
            $table->string('identication_card', 10)->nullable();  //cedula //Constraint below
            $table->string('ruc', 13)->nullable();  //Constraint below
            $table->string('company', 300)->nullable(); //razon social
            $table->string('name', 300)->nullable();    //nombre comercial
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('accounting')->default(false);
            $table->integer('receive_account_id')->nullable();  //Account
            $table->integer('discount')->nullable();            //Invoice

            // Accounting
            $table->integer('pay_account_id')->nullable();      //Account
            $table->integer('rent_retention')->nullable();      //% rent
            $table->integer('iva_retention')->nullable();       //% iva

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
