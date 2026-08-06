<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dav')) {
            return;
        }

        Schema::table('dav', function (Blueprint $table) {
            if (!Schema::hasColumn('dav', 'fiscal_nome')) {
                $table->string('fiscal_nome')->nullable()->after('subproduto');
            }

            if (!Schema::hasColumn('dav', 'fiscal_cargo')) {
                $table->string('fiscal_cargo')->nullable()->after('fiscal_nome');
            }
        });

        DB::table('dav')
            ->whereNull('fiscal_nome')
            ->update([
                'fiscal_nome' => 'Douglas Freitas de Almeida Filho',
                'fiscal_cargo' => 'Analista em Infraestrutura de Transportes - Eng. Civil',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('dav')) {
            return;
        }

        Schema::table('dav', function (Blueprint $table) {
            if (Schema::hasColumn('dav', 'fiscal_cargo')) {
                $table->dropColumn('fiscal_cargo');
            }

            if (Schema::hasColumn('dav', 'fiscal_nome')) {
                $table->dropColumn('fiscal_nome');
            }
        });
    }
};
