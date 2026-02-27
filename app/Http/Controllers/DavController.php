<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Empreendimento;
use App\Models\DavProfissionais;
use App\Models\Dav;
use App\Models\DavProfissionalDocumento;
use App\Models\DavProfissionalTrecho;
use App\Models\DavQuantidade;

class DavController extends Controller
{
    public function index()
    {
        $davs = Dav::withCount('profissionais')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Dav/Index', [
            'davs' => $davs,
            'auth' => [
                'user' => auth()->user()
            ]
        ]);
    }

    public function create()
    {
        $empreendimentos = Empreendimento::orderBy('cod_emp')
            ->get(['id', 'cod_emp', 'ose_emp']);

        $produtos = DB::table('subprodutos')
            ->select('desc_dav')
            ->whereNotNull('desc_dav')
            ->distinct()
            ->orderBy('desc_dav')
            ->pluck('desc_dav');

        $profissionais = DavProfissionais::orderBy('nome')
            ->get(['id', 'nome', 'formacao']);

        return Inertia::render('Dav/Create', [
            'empreendimentos' => $empreendimentos,
            'produtos' => $produtos,
            'profissionais' => $profissionais,
            'auth' => [
                'user' => auth()->user()
            ]
        ]);
    }

    public function buscarSubprodutos(Request $request)
    {
        $request->validate([
            'produto' => 'required|string'
        ]);

        $subprodutos = DB::table('subprodutos')
            ->where('desc_dav', $request->produto)
            ->selectRaw("DISTINCT CONCAT(subproduto, ' ', descricao_revisada) as nome")
            ->orderBy('subproduto')
            ->pluck('nome');

        return response()->json($subprodutos);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // 1️⃣ Criar o DAV principal
            $dav = Dav::create([
                'coordenador' => $request->coordenador,
                'empreendimento_id' => $request->empreendimento_id,
                'n_ose' => $request->ose,
                'produto' => $request->produto,
                'subproduto' => $request->subproduto,
                'status' => 'Pendente'
            ]);

            // 2️⃣ Salvar profissionais vinculados
            foreach ($request->profissionais as $item) {

                $profissionalDocumento = DavProfissionalDocumento::create([
                    'dav_id' => $dav->id,
                    'profissional_id' => $item['profissional_id'],
                    'funcao' => $item['funcao'],
                    'data_ini' => $item['data_ini'],
                    'data_fim' => $item['data_fim'],
                    'diarias' => $item['diarias'] ?? 0,
                ]);

                // 3️⃣ Salvar trechos
                if (!empty($item['trechos'])) {

                    foreach ($item['trechos'] as $trecho) {

                        DavProfissionalTrecho::create([
                            'dav_profissional_documento_id' => $profissionalDocumento->id,
                            'origem' => $trecho['origem'],
                            'destino' => $trecho['destino'],
                            'aereo_qtd' => $trecho['aereo_qtd'] ?? 0,
                            'aquatico_qtd' => $trecho['aquatico_qtd'] ?? 0,
                            'terrestre_pickup_qtd' => $trecho['terrestre_pickup_qtd'] ?? 0,
                            'terrestre_hatch_qtd' => $trecho['terrestre_hatch_qtd'] ?? 0,
                        ]);
                    }
                }
            }

            // ===============================
            // 4️⃣ CALCULAR TOTAIS DA DAV
            // ===============================

            $dav->load('profissionais.trechos');

            $totalDiarias = 0;
            $totalAereo = 0;
            $totalAquatico = 0;
            $totalHatch = 0;
            $totalPickup = 0;

            foreach ($dav->profissionais as $prof) {

                $totalDiarias += $prof->diarias ?? 0;

                foreach ($prof->trechos as $trecho) {
                    $totalAereo += $trecho->aereo_qtd ?? 0;
                    $totalAquatico += $trecho->aquatico_qtd ?? 0;
                    $totalHatch += $trecho->terrestre_hatch_qtd ?? 0;
                    $totalPickup += $trecho->terrestre_pickup_qtd ?? 0;
                }
            }

            // ===============================
            // 5️⃣ BUSCAR SALDOS
            // ===============================

            $saldoDiarias = DavQuantidade::where('tipo','diarias')->first();
            $saldoAereo = DavQuantidade::where('tipo','passagem_aerea')->first();
            $saldoAquatico = DavQuantidade::where('tipo','veiculo_aquatico')->first();
            $saldoHatch = DavQuantidade::where('tipo','veiculo_hatch')->first();
            $saldoPickup = DavQuantidade::where('tipo','veiculo_pickup')->first();

            // ===============================
            // 6️⃣ VERIFICAR SE FICARÁ NEGATIVO
            // ===============================

            $avisos = [];

            if (($saldoDiarias->quantidade_atual - $totalDiarias) < 0)
                $avisos[] = 'Diárias ultrapassaram o saldo disponível.';

            if (($saldoAereo->quantidade_atual - $totalAereo) < 0)
                $avisos[] = 'Passagem aérea ultrapassou o saldo disponível.';

            if (($saldoAquatico->quantidade_atual - $totalAquatico) < 0)
                $avisos[] = 'Veículo aquático ultrapassou o saldo disponível.';

            if (($saldoHatch->quantidade_atual - $totalHatch) < 0)
                $avisos[] = 'Veículo hatch ultrapassou o saldo disponível.';

            if (($saldoPickup->quantidade_atual - $totalPickup) < 0)
                $avisos[] = 'Veículo pickup ultrapassou o saldo disponível.';

            // ===============================
            // 7️⃣ DESCONTAR DO BANCO
            // ===============================

            DavQuantidade::where('tipo','diarias')
                ->decrement('quantidade_atual', $totalDiarias);

            DavQuantidade::where('tipo','passagem_aerea')
                ->decrement('quantidade_atual', $totalAereo);

            DavQuantidade::where('tipo','veiculo_aquatico')
                ->decrement('quantidade_atual', $totalAquatico);

            DavQuantidade::where('tipo','veiculo_hatch')
                ->decrement('quantidade_atual', $totalHatch);

            DavQuantidade::where('tipo','veiculo_pickup')
                ->decrement('quantidade_atual', $totalPickup);

            // ===============================
            // 8️⃣ SALVAR SNAPSHOT NA DAV
            // ===============================

            // Recarregar os saldos já atualizados
            $saldoDiariasAtual = DavQuantidade::where('tipo','diarias')->value('quantidade_atual');
            $saldoAereoAtual = DavQuantidade::where('tipo','passagem_aerea')->value('quantidade_atual');
            $saldoAquaticoAtual = DavQuantidade::where('tipo','veiculo_aquatico')->value('quantidade_atual');
            $saldoHatchAtual = DavQuantidade::where('tipo','veiculo_hatch')->value('quantidade_atual');
            $saldoPickupAtual = DavQuantidade::where('tipo','veiculo_pickup')->value('quantidade_atual');

            // Salvar na DAV (snapshot histórico)
            $dav->update([
                'diarias_total' => $saldoDiariasAtual,
                'aereo_total' => $saldoAereoAtual,
                'aquatico_total' => $saldoAquaticoAtual,
                'hatch_total' => $saldoHatchAtual,
                'pickup_total' => $saldoPickupAtual,
            ]);

            DB::commit();

            return redirect()->route('dav.index')
                ->with('success', 'DAV criada com sucesso.')
                ->with('warning', count($avisos) ? implode(' ', $avisos) : null);

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors($e->getMessage());
        }
    }

    public function show(Dav $dav)
    {
        $dav->load([
            'profissionais.profissional',
            'profissionais.trechos',
            'empreendimento'
        ]);

        // CALCULAR TOTAIS DA DAV
        $totalDiarias = 0;
        $totalAereo = 0;
        $totalAquatico = 0;
        $totalHatch = 0;
        $totalPickup = 0;

        foreach ($dav->profissionais as $prof) {

            $totalDiarias += $prof->diarias ?? 0;

            foreach ($prof->trechos as $trecho) {
                $totalAereo += $trecho->aereo_qtd ?? 0;
                $totalAquatico += $trecho->aquatico_qtd ?? 0;
                $totalHatch += $trecho->terrestre_hatch_qtd ?? 0;
                $totalPickup += $trecho->terrestre_pickup_qtd ?? 0;
            }
        }


        // BUSCAR SALDOS ATUAIS
        $saldos = DavQuantidade::pluck('quantidade_atual', 'tipo');

        return Inertia::render('Dav/Show', [
            'dav' => $dav,
            'resumo' => [
                'totais' => [
                    'diarias' => $totalDiarias,
                    'aereo' => $totalAereo,
                    'aquatico' => $totalAquatico,
                    'hatch' => $totalHatch,
                    'pickup' => $totalPickup,
                ],
                'saldos' => [
                    'diarias' => $saldos['diarias'] ?? 0,
                    'aereo' => $saldos['passagem_aerea'] ?? 0,
                    'aquatico' => $saldos['veiculo_aquatico'] ?? 0,
                    'hatch' => $saldos['veiculo_hatch'] ?? 0,
                    'pickup' => $saldos['veiculo_pickup'] ?? 0,
                ]
            ],
            'auth' => [
                'user' => auth()->user()
            ]
        ]);
    }


    public function storeProfissional(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'formacao' => 'required|string|max:255',
        ]);

        $profissional = DavProfissionais::create([
            'nome' => $request->nome,
            'formacao' => $request->formacao,
        ]);

        return response()->json($profissional);
    }


    public function download(Dav $dav)
    {
        $dav->load([
            'profissionais.profissional',
            'profissionais.trechos',
            'empreendimento'
        ]);


        // CALCULAR TOTAIS DA DAV
        $totalDiarias = 0;
        $totalAereo = 0;
        $totalAquatico = 0;
        $totalHatch = 0;
        $totalPickup = 0;

        foreach ($dav->profissionais as $prof) {

            $totalDiarias += $prof->diarias ?? 0;

            foreach ($prof->trechos as $trecho) {
                $totalAereo += $trecho->aereo_qtd ?? 0;
                $totalAquatico += $trecho->aquatico_qtd ?? 0;
                $totalHatch += $trecho->terrestre_hatch_qtd ?? 0;
                $totalPickup += $trecho->terrestre_pickup_qtd ?? 0;
            }
        }

        // BUSCAR SALDOS ATUAIS
        $saldos = \App\Models\DavQuantidade::pluck('quantidade_atual', 'tipo');

        $resumo = [
            'totais' => [
                'diarias' => $totalDiarias,
                'aereo' => $totalAereo,
                'aquatico' => $totalAquatico,
                'hatch' => $totalHatch,
                'pickup' => $totalPickup,
            ],
            'saldos' => [
                'diarias' => $saldos['diarias'] ?? 0,
                'aereo' => $saldos['passagem_aerea'] ?? 0,
                'aquatico' => $saldos['veiculo_aquatico'] ?? 0,
                'hatch' => $saldos['veiculo_hatch'] ?? 0,
                'pickup' => $saldos['veiculo_pickup'] ?? 0,
            ]
        ];

        $pdf = Pdf::loadView('dav.pdf', [
            'dav' => $dav,
            'resumo' => $resumo
        ])->setPaper('a4');

        return $pdf->download("DAV-{$dav->id}.pdf");
    }

}