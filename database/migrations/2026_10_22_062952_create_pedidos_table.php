<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePedidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained('modelos');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->string('cliente_nombre');
            $table->string('cliente_email');
            $table->decimal('total', 12, 2);
            $table->string('estado')->default('pendiente');  // pendiente, confirmado, cancelado
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
        Schema::dropIfExists('pedidos');
    }
}
