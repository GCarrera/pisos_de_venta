<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePisoVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('piso_ventas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre');
            $table->text("ubicacion");
            $table->decimal('dinero', 20, 2);
            $table->decimal('gain', 20, 2)->default('0.00');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('piso_ventas');
    }
}
