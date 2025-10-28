<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Oficio;
use App\Models\RegistrosOficio;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class OficiosController extends Controller
{
    public function index()
    {
        return inertia('Oficios/Index', [
            'user' => auth()->user(),
        ]);
    }

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

        $ultimo = DB::table('registros_oficios')
            ->whereYear('data_registro', $ano)
            ->max('contador');

        if (is_null($ultimo)) {
            $request->validate([
                'contador' => 'required|integer|min:1',
            ]);
            $contador = (int) $request->contador;
        } else {
            $contador = $ultimo + 1;
        }

        $tipo = $request->boolean('oficio_dnit') ? '02' : ($request->boolean('oficio_sede') ? '01' : '');
        $rodoviaSan = $request->rodovia ? preg_replace('/[\s\/-]+/', '', $request->rodovia) : '';

        $oficio_num = $tipo
            ? "OF_JGP.{$tipo}.{$contador}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : '')
            : ($request->oficio_num ?? "OF_JGP.__.{$contador}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : ''));

        $id = DB::table('registros_oficios')->insertGetId([
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
            ->route('oficios.index')
            ->with('success', 'Ofício registrado com sucesso!')
            ->with('oficio_id', $id);

    }


    public function listar(Request $request)
    {
        $query = RegistrosOficio::query();

        if ($request->filled('rodovia')) {
            $query->where('rodovia', $request->rodovia);
        }

        $oficios = $query->orderBy('created_at', 'desc')->get();

        return response()->json($oficios);
    }

    public function show($id)
    {
        $oficio = DB::table('registros_oficios')->where('id', $id)->first();

        if (!$oficio) {
            return response()->json(['error' => 'Ofício não encontrado'], 404);
        }

        return response()->json($oficio);
    }

    public function listarView()
    {
        return inertia('Oficios/Listar', [
            'user' => auth()->user(),
        ]);
    }

    private function resolverTipo(bool $dnit, bool $sede): string
    {
        if ($dnit) return '02';
        if ($sede) return '01';
        return '';
    }

    private function sanitizarRodovia(string $rodovia): string
    {
        return preg_replace('/[\s\/-]/', '', $rodovia);
    }

    private function montarNumeroOficio(string $tipo, int $sequencia, int $ano, string $rodoviaSan): string
    {
        if ($tipo === '') {
            return "OF_JGP..{$sequencia}/{$ano}_{$rodoviaSan}";
        }
        return "OF_JGP.{$tipo}.{$sequencia}/{$ano}_{$rodoviaSan}";
    }

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

        $pdf = Pdf::loadView('pdf.oficio', $data);
        return $pdf->download("Oficio-{$oficio->id}.pdf");
    }
}
