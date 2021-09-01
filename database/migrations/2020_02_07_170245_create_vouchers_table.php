<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->bigInteger('movement_id')->unsigned();
            $table->primary('movement_id');
            $table->string('serie', 17); //Serie Establecimiento, punto de emisión y secuencia.
            $table->bigInteger('contact_id')->unsigned(); //Id provider, foreign key provider.
            // $table->date('date');   //date movement
            $table->bigInteger('doc_realeted')->nullable();
            $table->integer('expiration_days')->default(0);
            $table->decimal('no_iva', 10, 2)->default(0);
            $table->decimal('base0', 10, 2)->default(0);
            $table->decimal('base12', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);    //value iva
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->smallInteger('voucher_type'); //Type voucher 01-F / 03-NC / 04-NC / 05-ND
            $table->char('pay_method', 8)->nullable(); //efectivo/credito
            // $table->string('notes')->nullable();    //descripcion movement
            $table->decimal('paid', 10, 2)->default(0);   //Mount paid <= total ... parcial mount paid

            //Comprobante Electronica Inicio +++++++++++++++
            //CREADO-ENVIADO-RECIBIDA-DEVUELTA-ACEPTADO-RECHAZADO-EN_PROCESO-AUTORIZADO-NO_AUTORIZADO-CANCELADO
            $table->char('state', 15)->default('CREADO');
            $table->date('autorized')->nullable()->default(NULL);
            $table->string('authorization', 49)->nullable()->default(NULL);
            $table->decimal('iva_retention', 10, 2)->default(0);
            $table->decimal('rent_retention', 10, 2)->default(0);
            $table->string('xml')->nullable();
            $table->string('extra_detail')->nullable();
            //Comprobante Electronica Fin ++++++++++++++++++

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('movement_id')->references('id')->on('movements');
            $table->foreign('contact_id')->references('id')->on('contacts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
}
