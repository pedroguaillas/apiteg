<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRetentionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retentions', function (Blueprint $table) {
            $table->bigInteger('vaucher_id')->unsigned();
            $table->primary('vaucher_id');
            $table->string('serie', 17);                //serie retention
            $table->date('date');                       //date retention <fechaEmision>

            //Retencion Electronica Inicio +++++++++++++++
            //CREADO-ENVIADO-RECIBIDA-DEVUELTA-ACEPTADO-RECHAZADO-EN_PROCESO-AUTORIZADO-NO_AUTORIZADO-CANCELADO
            $table->char('state', 15)->default('CREADO');
            $table->string('authorization', 49)->nullable()->default(NULL);
            $table->string('xml')->nullable();
            $table->string('extra_detail')->nullable();
            //Retencion Electronica Fin ++++++++++++++++++

            $table->timestamps();

            $table->foreign('vaucher_id')->references('movement_id')->on('vouchers');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retentions');
    }
}
