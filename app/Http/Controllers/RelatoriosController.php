<?php

namespace App\Http\Controllers;

use App\Models\Relatorio;
use Illuminate\Support\Facades\DB;

class RelatoriosController extends Controller
{
    public function gerarNovo()
    {
        DB::transaction(function () {

            // pega o relatório ativo atual
            $relatorioAtual = Relatorio::where('ativo', 1)->first();

            // define o próximo número
            $proximoNumero = $relatorioAtual
                ? $relatorioAtual->numero + 1
                : 1;

            // desativa o atual
            if ($relatorioAtual) {
                $relatorioAtual->update(['ativo' => 0]);
            }

            // cria o novo relatório
            Relatorio::create([
                'numero' => $proximoNumero,
                'ativo' => 1,
            ]);
        });

        return redirect()->back();
    }
}
