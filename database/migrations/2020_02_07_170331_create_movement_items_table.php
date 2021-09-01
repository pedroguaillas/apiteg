<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovementItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movement_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('movement_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 14, 6);
            $table->decimal('price', 14, 6);
            $table->decimal('discount', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('movement_id')->references('id')->on('movements');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movement_items');
    }
}
