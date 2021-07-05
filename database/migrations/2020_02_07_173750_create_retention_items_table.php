<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRetentionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retention_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('code');        //1-Imp. Renta/2-IVA
            $table->string('tax_code');     //Foreign Key Tax
            $table->decimal('base', 14, 6);   //base to retention
            $table->decimal('porcentage', 5, 2); //Not all tax contain porcentage retention
            $table->decimal('value', 14, 6);        //Shuld modify value & porcentage
            $table->bigInteger('retention_id')->unsigned(); //Foreign Key Sale

            $table->foreign('tax_code')->references('code')->on('taxes');
            $table->foreign('retention_id')->references('vaucher_id')->on('retentions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retention_items');
    }
}
