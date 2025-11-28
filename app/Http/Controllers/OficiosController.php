<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RegistrosOficio;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Response;

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


    public function listar(Request $request)
    {
        $query = DB::table('registros_oficios')
            ->leftJoin('users', 'users.id', '=', 'registros_oficios.autor')
            ->select(
                'registros_oficios.*',
                'users.name as autor_nome'
            );

        if ($request->filled('rodovia')) {
            $query->where('rodovia', $request->rodovia);
        }

        $oficios = $query->orderBy('registros_oficios.created_at', 'desc')->get();

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



   public function download($id)
    {
        $oficio = DB::table('registros_oficios')->find($id);
        if (!$oficio) {
            abort(404, 'Ofício não encontrado.');
        }

        // 🔹 1) SE EXISTIR ARQUIVO PERSONALIZADO, BAIXAR ELE
        if (!empty($oficio->arquivo_personalizado)) {
            $caminho = storage_path('app/public/' . $oficio->arquivo_personalizado);

            if (file_exists($caminho)) {

                // cria nome baseado no número do ofício
                $nomeLimpo = preg_replace('/[\/\\\\]+/', '-', (string)($oficio->oficio_num ?? 'Oficio'));
                $nomeDownload = "Oficio-{$nomeLimpo}.docx";

                return response()->download($caminho, $nomeDownload);
            }
        }


        // 🔹 2) SENÃO, GERAR O MODELO AUTOMATICAMENTE
        $modeloPath = public_path('Modelo_Oficio_Placeholders.docx');
        if (!file_exists($modeloPath)) {
            abort(500, 'Modelo de ofício não encontrado.');
        }

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $template = new \PhpOffice\PhpWord\TemplateProcessor($modeloPath);

        $processos = [
            'CT-94-2022' => '50600.011613/2022-03',
            'BR-230/MA' => '50600.010066/2018-54',
            'BR-437 CE/RN' => '50600.003544/2020-94',
            'BR-402 MA/PI' => '50600.029435/2022-69',
            'BR-116 CE' => '50603.002112/2022-06',
            'BR-020 GO/BA' => '50600.010068/2018-43',
            'BR-304 RN' => '50614.001281/2015-62',
            'BR-316 PI' => '50618.000831/2023-04',
            'BR-104 RN' => '50614.000423/2024-65',
            'BR-030 BA' => '50600.032816/2023-14',
            'BR-122 BA' => '50605.000071/2019-90',
            'BR-316 PI (km 33,54 ao km 55,60)' => '50618.000831/2023-04',
            'BR-110/316/PE' => '50600.043127/2022-46',
            'BR-349/SE/AL' => '50600.036707/2023-68',
            'BR-135/BA' => '50600.510964/2017-27',
            'BR-324/BA' => '50605.002443/2024-80',
            'BR-316/MA' => '50600.034479/2024-72',
            'BR-226/CE' => '50603.001120/2024-99',
            'BR-010/MA' => '50600.033749/2024-28',
            'BR-104/AL' => '50600.005357/2025-50',
            'BR-222/CE' => '50600.034578/2024-54'
        ];
        $processoSEI = $processos[$oficio->rodovia] ?? '';

        $dataFormatada = Carbon::parse($oficio->data_registro)
            ->translatedFormat('d \\d\\e F \\d\\e Y');

        $template->setValue('oficio_numero', $oficio->oficio_num ?? '');
        $template->setValue('data_oficio', $dataFormatada);
        $template->setValue('assunto', $oficio->assunto ?? '');
        $template->setValue('texto_oficio', (string)($oficio->texto ?? ''));
        $template->setValue('processo_sei', $processoSEI);

        $arquivoSaida = storage_path('app/public/Oficio-' . $oficio->id . '.docx');
        $template->saveAs($arquivoSaida);

        $nomeArquivo = preg_replace('/[\/\\\\]+/', '-', (string)($oficio->oficio_num ?? 'Oficio'));

        return response()->download($arquivoSaida, "Oficio-{$nomeArquivo}.docx")
            ->deleteFileAfterSend(true);
    }

    
    private function sanitizarRodovia(string $rodovia): string
    {
        $rodoviaSan = preg_replace('/[\s\/-]+/', '', $rodovia);

        return $rodoviaSan;
    }

        private function montarNumeroOficio(string $tipo, int $sequencia, int $ano, string $rodoviaSan): string
    {
        if (stripos($rodoviaSan, 'CT942022') !== false) {
            $rodoviaOriginal = 'CT-94-2022';

            if ($tipo === '') {
                return "OF_JGP.__.{$sequencia}/{$ano}-{$rodoviaOriginal}";
            }

            return "OF_JGP.{$tipo}.{$sequencia}/{$ano}-{$rodoviaOriginal}";
        }

        if ($tipo === '') {
            return "OF_JGP.__.{$sequencia}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : '');
        }

        return "OF_JGP.{$tipo}.{$sequencia}/{$ano}" . ($rodoviaSan ? "_{$rodoviaSan}" : '');
    }

    private function atualizarRodoviaNoNumero($oficioNum, $novaRodovia)
    {
        // Sanitiza rodovia nova
        $rodoviaSan = $this->sanitizarRodovia($novaRodovia);

        // 1) Caso especial CT-94-2022 → final é com hífens
        if ($novaRodovia === 'CT-94-2022') {

            // Substituir qualquer coisa após o ano por `-CT-94-2022`
            return preg_replace(
                '/\/\d{4}.*/',
                '/' . date('Y') . '-CT-94-2022',
                $oficioNum
            );
        }

        // 2) Rodovias normais → formato: _BR437CERN

        // Remove parte antiga da rodovia no final
        $semRodoviaAntiga = preg_replace('/(_[A-Za-z0-9]+$|-[A-Za-z0-9-]+$)/', '', $oficioNum);

        // Junta com nova rodovia sanitizada
        return $semRodoviaAntiga . '_' . $rodoviaSan;
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'rodovia' => 'required|string',
            'assunto' => 'required|string',
            'texto_oficio' => 'required|string',
            'oficio_sede' => 'nullable|boolean',
            'oficio_dnit' => 'nullable|boolean',
        ]);

        $oficio = DB::table('registros_oficios')->find($id);

        if (!$oficio) {
            return response()->json(['error' => 'Ofício não encontrado'], 404);
        }

        // Determina novo tipo
        $tipo = '';
        if ($request->oficio_dnit) $tipo = '02';
        if ($request->oficio_sede) $tipo = '01';

        // 1️⃣ Atualiza APENAS o tipo, se marcado
        $novoNumero = $oficio->oficio_num;
        if ($tipo !== '') {
            $novoNumero = $this->atualizarTipoNoNumero($novoNumero, $tipo);
        }

        // 2️⃣ Atualiza a rodovia dentro do numero
        $novoNumero = $this->atualizarRodoviaNoNumero($novoNumero, $request->rodovia);

        DB::table('registros_oficios')
            ->where('id', $id)
            ->update([
                'rodovia' => $request->rodovia,
                'assunto' => $request->assunto,
                'texto' => $request->texto_oficio,
                'oficio_num' => $novoNumero,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    private function atualizarTipoNoNumero($oficioNum, $novoTipo)
    {
        // Ex: OF_JGP.02.221/2025_BR437CERN
        return preg_replace(
            '/OF_JGP\.(..)\./',
            "OF_JGP.{$novoTipo}.",
            $oficioNum
        );
    }


    public function uploadArquivoPersonalizado(Request $request, $id)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:doc,docx|max:10240'
        ]);

        // Buscar o registro
        $oficio = DB::table('registros_oficios')->find($id);

        // Se já existe arquivo, apagar antes de substituir
        if (!empty($oficio->arquivo_personalizado)) {
            $oldPath = storage_path('app/public/' . $oficio->arquivo_personalizado);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Salva o novo arquivo
        $path = $request->file('arquivo')->store('oficios_personalizados', 'public');

        DB::table('registros_oficios')
            ->where('id', $id)
            ->update(['arquivo_personalizado' => $path]);

        return response()->json(['success' => true, 'path' => $path]);
    }

    public function removerArquivoPersonalizado($id)
    {
        $oficio = DB::table('registros_oficios')->find($id);

        if (!$oficio) {
            return response()->json(['error' => 'Ofício não encontrado'], 404);
        }

        // apagar arquivo físico
        if (!empty($oficio->arquivo_personalizado)) {
            $path = storage_path('app/public/' . $oficio->arquivo_personalizado);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // limpar coluna
        DB::table('registros_oficios')->where('id', $id)
            ->update(['arquivo_personalizado' => null]);

        return response()->json(['success' => true]);
    }


}