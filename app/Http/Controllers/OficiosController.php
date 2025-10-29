<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RegistrosOficio;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class OficiosController extends Controller
{
    /**
     * Página de cadastro de novo ofício
     */
    public function index()
    {
        return inertia('Oficios/Index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Retorna o último número de contador do ano informado
     */
    public function ultimoContador(Request $request)
    {
        $ano = (int) ($request->input('ano') ?: now()->year);

        $ultimo = DB::table('registros_oficios')
            ->whereYear('data_registro', $ano)
            ->max('contador');

        return response()->json([
            'ano' => $ano,
            'ultimo_contador' => $ultimo,
        ]);
    }

    /**
     * Armazena um novo ofício
     */
    public function store(Request $request)
    {
        $request->validate([
            'rodovia'       => 'required|string',
            'data_oficio'   => 'required|date',
            'assunto'       => 'required|string',
            'texto_oficio'  => 'required|string',
            'oficio_dnit'   => 'nullable|boolean',
            'oficio_sede'   => 'nullable|boolean',
            'contador'      => 'nullable|integer|min:1',
        ]);

        $ano = Carbon::parse($request->data_oficio)->year;

        // 🔸 Verifica último contador do ano
        $ultimo = DB::table('registros_oficios')
            ->whereYear('data_registro', $ano)
            ->max('contador');

        // 🔸 Se for o primeiro ofício do ano, obriga a informar contador manual
        if (is_null($ultimo)) {
            $request->validate([
                'contador' => 'required|integer|min:1',
            ]);
            $contador = (int) $request->contador;
        } else {
            $contador = $ultimo + 1;
        }

        $tipo = $this->resolverTipo(
            $request->boolean('oficio_dnit'),
            $request->boolean('oficio_sede')
        );

        $rodoviaSan = $this->sanitizarRodovia($request->rodovia);

        $oficio_num = $this->montarNumeroOficio($tipo, $contador, $ano, $rodoviaSan);

        DB::table('registros_oficios')->insertGetId([
            'rodovia'        => $request->rodovia,
            'data_registro'  => Carbon::parse($request->data_oficio)->startOfDay(),
            'oficio_num'     => $oficio_num,
            'assunto'        => $request->assunto,
            'texto'          => $request->texto_oficio,
            'autor'          => auth()->id(),
            'contador'       => $contador,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()
            ->route('oficios.listar.view')
            ->with('success', 'Ofício registrado com sucesso!');
    }

    /**
     * Lista os ofícios via JSON (para DataTable ou fetch)
     */
    public function listar(Request $request)
    {
        $query = RegistrosOficio::query();

        if ($request->filled('rodovia')) {
            $query->where('rodovia', $request->rodovia);
        }

        $oficios = $query->orderBy('created_at', 'desc')->get();

        return response()->json($oficios);
    }

    /**
     * Retorna um ofício específico
     */
    public function show($id)
    {
        $oficio = DB::table('registros_oficios')->where('id', $id)->first();

        if (!$oficio) {
            return response()->json(['error' => 'Ofício não encontrado'], 404);
        }

        return response()->json($oficio);
    }

    /**
     * Página de listagem
     */
    public function listarView()
    {
        return inertia('Oficios/Listar', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Gera e baixa o PDF do ofício
     */
    public function gerarPdf($id)
    {
        $oficio = DB::table('registros_oficios')->where('id', $id)->first();

        if (!$oficio) {
            abort(404, 'Ofício não encontrado');
        }

        $data = [
            'oficio_num'   => $oficio->oficio_num,
            'assunto'      => $oficio->assunto,
            'texto_oficio' => $oficio->texto,
            'data_oficio'  => Carbon::parse($oficio->data_registro)->translatedFormat('d \d\e F \d\e Y'),
        ];

        $pdf = Pdf::loadView('oficio', $data);
        return $pdf->download("Oficio-{$oficio->id}.pdf");
    }

    /**
     * -------------------------------
     * Métodos auxiliares
     * -------------------------------
     */
    private function resolverTipo(bool $dnit, bool $sede): string
    {
        if ($dnit) return '02';
        if ($sede) return '01';
        return '';
    }

    private function sanitizarRodovia(string $rodovia): string
    {
        return preg_replace('/[\s\/-]+/', '', $rodovia);
    }

    private function montarNumeroOficio(string $tipo, int $sequencia, int $ano, string $rodoviaSan): string
    {
        if ($tipo === '') {
            return "OF_JGP.__.{$sequencia}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : '');
        }
        return "OF_JGP.{$tipo}.{$sequencia}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : '');
    }
}
