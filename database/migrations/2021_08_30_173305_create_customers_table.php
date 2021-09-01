<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migrar consumidor final cliente por defecto
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id');
            $table->integer('state')->default(1);   //1-activo/2-inactivo
            $table->string('type_identification', 10);    //cedula/ruc/pasaporte /Consumidor final no se registrara
            $table->string('identication', 13)->nullable();
            $table->string('name', 300);    //nombre comercial
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('accounting')->default(false);
            $table->integer('discount')->nullable();            //Invoice

            // Accounting
            $table->integer('rent_retention')->nullable();      //% rent
            $table->integer('iva_retention')->nullable();       //% iva

            $table->timestamps();

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
        Schema::dropIfExists('customers');
    }
}