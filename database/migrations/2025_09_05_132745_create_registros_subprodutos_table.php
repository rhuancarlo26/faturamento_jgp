<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegistrosSubprodutosTable extends Migration
{
    public function up()
    {
        Schema::create('registros_subprodutos', function (Blueprint $table) {
            $table->id();
            $table->string('rodovia');
            $table->date('data_aprovacao');
            $table->string('sei')->nullable();
            $table->string('oficio_numero')->nullable();
            $table->string('sei_versao_aprovada')->nullable();
            $table->string('subproduto');
            $table->string('cod_siac')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('registros_subprodutos');
    }
}