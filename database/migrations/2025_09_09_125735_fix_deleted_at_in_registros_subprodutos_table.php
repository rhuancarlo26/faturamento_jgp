<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixDeletedAtInRegistrosSubprodutosTable extends Migration
{
    public function up()
    {
        Schema::table('registros_subprodutos', function (Blueprint $table) {
            if (Schema::hasColumn('registros_subprodutos', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $table->softDeletes()->after('id_user');
        });
    }

    public function down()
    {
        Schema::table('registros_subprodutos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}