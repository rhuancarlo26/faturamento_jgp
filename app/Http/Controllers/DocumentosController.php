<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Relatorio;
use App\Models\Documento;
use App\Models\Profissional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentosController extends Controller
{
    public function index(Request $request)
    {
        // Relatório selecionado via histórico (se existir)
        $relatorioSelecionado = $request->relatorio_id
            ? Relatorio::find($request->relatorio_id)
            : null;

        // Relatório atual (ativo)
        $relatorioAtual = Relatorio::where('ativo', 1)->first();

        // Define qual relatório carregar
        $relatorioEmUso = $relatorioSelecionado ?? $relatorioAtual;

        $documentos = $relatorioEmUso
            ? Documento::with('profissional')
                ->where('relatorio_id', $relatorioEmUso->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $profissionais = Profissional::where('ativo', 1)
            ->orderBy('nome')
            ->get();

        // TODOS os relatórios para o histórico (com contagem)
        $relatorios = Relatorio::withCount('documentos')
            ->orderByDesc('numero')
            ->get();

        return Inertia::render('Documentos/Index', [
            'documentos'        => $documentos,
            'profissionais'     => $profissionais,
            'relatorioAtual'    => $relatorioAtual,
            'relatorioEmUso'    => $relatorioEmUso,
            'relatorios'        => $relatorios,
            'user' => auth()->user(),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'profissional_id' => 'required|exists:profissionais,id',
            'arquivo' => 'required|mimes:pdf',
        ]);

        $relatorioAtual = Relatorio::where('ativo', 1)->firstOrFail();

        $path = $request->file('arquivo')->store('documentos', 'public');

        Documento::create([
            'profissional_id' => $request->profissional_id,
            'relatorio_id' => $relatorioAtual->id,
            'arquivo' => $path,
        ]);

        return redirect()->back();
    }


    public function download($id)
    {
        $documento = Documento::with('profissional')->findOrFail($id);

        if (!Storage::disk('public')->exists($documento->arquivo)) {
            abort(404);
        }

        $nomeArquivo = $documento->profissional->nome . '.pdf';

        return Storage::disk('public')->download(
            $documento->arquivo,
            $nomeArquivo
        );
    }


    public function destroy($id)
    {
        $documento = DB::table('documentos')->where('id', $id)->first();

        if (!$documento) {
            abort(404);
        }

        // Remove o arquivo físico
        if ($documento->arquivo && Storage::exists($documento->arquivo)) {
            Storage::delete($documento->arquivo);
        }

        // Remove do banco
        DB::table('documentos')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Documento excluído com sucesso.');
    }

    public function visualizar($id)
    {
        $documento = DB::table('documentos')->where('id', $id)->first();

        if (!$documento) {
            abort(404);
        }

        $path = storage_path('app/public/' . $documento->arquivo);

        if (!file_exists($path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    public function historico($relatorioId)
    {
        $relatorio = Relatorio::findOrFail($relatorioId);

        $documentos = Documento::with('profissional')
            ->where('relatorio_id', $relatorio->id)
            ->get();

        return Inertia::render('Documentos/Historico', [
            'relatorio' => $relatorio,
            'documentos' => $documentos,
        ]);
    }





}
