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
use App\Models\Subproduto;
use Illuminate\Support\Facades\Mail;
use App\Mail\DavStatusMail;
use App\Models\User;
use Carbon\Carbon;

class DavController extends Controller
{
    private function getFiscalAtual(): array
    {
        return [
            'nome' => config('dav.fiscal.nome', 'Alberto Yoshikasu Maeda'),
            'cargo' => config('dav.fiscal.cargo', 'Coordenador de Estudos e Projetos Ambientais'),
        ];
    }

    public function index(Request $request)
    {
        $query = Dav::with('empreendimento');

        if ($request->empreendimento_id) {
            $query->where('empreendimento_id', $request->empreendimento_id);
        }

        if ($request->produto) {
            $query->where('produto', $request->produto);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $davs = $query
            ->withCount('profissionais')
            ->withMin('profissionais', 'data_ini')
            ->orderByDesc('id')
            ->get()
             ->each(function (Dav $dav) {
                if (!$dav->profissionais_min_data_ini) {
                    $dav->setAttribute('prazo_solicitacao', null);
                    return;
                }

                $dataSolicitacao = Carbon::parse($dav->created_at)->startOfDay();
                $dataViagem = Carbon::parse($dav->profissionais_min_data_ini)->startOfDay();
                $diasAteViagem = $dataSolicitacao->diffInDays($dataViagem, false);

                $dav->setAttribute('prazo_solicitacao', [
                    'dias' => $diasAteViagem,
                    'situacao' => $diasAteViagem < 0
                        ? 'pos_periodo'
                        : ($diasAteViagem < 15 ? 'atrasado' : 'no_prazo'),
                    'data_viagem' => $dataViagem->toDateString(),
                    'data_solicitacao' => $dataSolicitacao->toDateString(),
                ]);
            })
            ->groupBy(function ($dav) {
                return $dav->dav_pai_id ?? $dav->id;
            })
            ->values();

        $empreendimentos = Empreendimento::whereIn(
            'id',
            Dav::select('empreendimento_id')->distinct()
        )->orderBy('cod_emp')->get(['id','cod_emp']);

        $produtos = Dav::select('produto')
            ->distinct()
            ->orderBy('produto')
            ->pluck('produto');

        $status = Dav::select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return Inertia::render('Dav/Index', [
            'davs' => $davs,
            'empreendimentos' => $empreendimentos,
            'produtos' => $produtos,
            'statusList' => $status,
            'filtros' => $request->only([
                'empreendimento_id',
                'produto',
                'status'
            ]),
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

        $subprodutos = Subproduto::where('desc_dav', $request->produto)
            ->orderBy('subproduto')
            ->get()
            ->map(function ($item) {
                return $item->subproduto . ' ' . $item->descricao_revisada;
            });

        return response()->json($subprodutos);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $fiscalAtual = $this->getFiscalAtual();

            // 1️⃣ Criar o DAV principal
            $dav = Dav::create([
                'coordenador' => $request->coordenador,
                'empreendimento_id' => $request->empreendimento_id,
                'n_ose' => $request->ose,
                'n_sei_ose' => $request->n_sei_ose,
                'produto' => $request->produto,
                'subproduto' => $request->subproduto,
                'fiscal_nome' => $fiscalAtual['nome'],
                'fiscal_cargo' => $fiscalAtual['cargo'],
                'status' => 'Pendente'
            ]);

            // 2️⃣ Salvar profissionais vinculados
            foreach ($request->profissionais as $item) {

                $profissionalDocumento = DavProfissionalDocumento::create([
                    'dav_id' => $dav->id,
                    'profissional_id' => $item['profissional_id'],
                    'funcao' => $item['funcao'],
                    'desc_trecho' => $item['desc_trecho'] ?? null,
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

            // 📧 Notificar via email
            $emails = [
                'rhuan.borges@jgpconsultoria.com.br',
                'alberto.maeda@dnit.gov.br'
            
            ];

            foreach ($emails as $email) {
                Mail::to($email)->send(new DavStatusMail($dav));
            }

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

        // ===============================
        // 🔎 CONTROLE DE VERSÃO
        // ===============================

        $raizId = $dav->dav_pai_id ?? $dav->id;

        $ultimaDav = Dav::where(function ($q) use ($raizId) {
                $q->where('id', $raizId)
                ->orWhere('dav_pai_id', $raizId);
            })
            ->orderByDesc('versao')
            ->first();

        $isUltimaVersao = $dav->id === $ultimaDav->id;

        // ===============================
        // 📊 CALCULAR TOTAIS DA DAV
        // ===============================

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
        // 💰 BUSCAR SALDOS ATUAIS
        // ===============================

        $saldos = DavQuantidade::pluck('quantidade_atual', 'tipo');

        return Inertia::render('Dav/Show', [
            'dav' => $dav,
            'isUltimaVersao' => $isUltimaVersao, // 👈 NOVO
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
            'empreendimento',
            'aprovador'
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


    public function aprovar(Dav $dav)
    {
        if (auth()->user()->role !== 'Fiscal') {
            abort(403, 'Sem permissão.');
        }

        if ($dav->status !== 'Pendente') {
            return back()->with('error', 'DAV já foi analisado.');
        }

        $dav->update([
            'status' => 'Aprovado',
            'aprovado_por' => auth()->id(),
            'aprovado_em' => now(),
        ]);

         $emails = [

            'elenito.libanio@jgpconsultoria.com.br',
            'carolina.sampaio@jgpconsultoria.com.br',
            'josecarlos.pereira@jgpconsultoria.com.br',
            'alberto.maeda@dnit.gov.br',
            'rhuan.borges@jgpconsultoria.com.br'

        ];

        foreach ($emails as $email) {
            Mail::to($email)->send(new DavStatusMail($dav));
        }
        

        return back()->with('success', 'DAV aprovado com sucesso.');
    }

    public function reprovar(Request $request, Dav $dav)
    {
        if (auth()->user()->role !== 'Fiscal') {
            abort(403);
        }

        if ($dav->status !== 'Pendente') {
            return back()->with('error', 'DAV já foi analisado.');
        }

        $request->validate([
            'motivo' => 'required|string|min:5'
        ]);

        $dav->update([
            'status' => 'Reprovado',
            'motivo_reprovacao' => $request->motivo
        ]);

        $emails = [
            'elenito.libanio@jgpconsultoria.com.br',
            'carolina.sampaio@jgpconsultoria.com.br',
            'josecarlos.pereira@jgpconsultoria.com.br',
            'rhuan.borges@jgpconsultoria.com.br'
        
        ];

        foreach ($emails as $email) {
            Mail::to($email)->send(new DavStatusMail($dav));
        }

        return back()->with('success', 'DAV reprovado com sucesso.');
    }

    public function retificar(Dav $dav)
    {
        DB::beginTransaction();

        try {
            $fiscalAtual = $this->getFiscalAtual();

            if ($dav->status !== 'Reprovado') {
                return back()->with('error', 'Só é possível retificar DAV Reprovada.');
            }

            // Descobrir raiz
            $raizId = $dav->dav_pai_id ?? $dav->id;

            // Descobrir última versão do grupo
            $ultimaDav = Dav::where(function ($q) use ($raizId) {
                    $q->where('id', $raizId)
                    ->orWhere('dav_pai_id', $raizId);
                })
                ->orderByDesc('versao')
                ->first();

            // 🔒 BLOQUEAR se não for a mais recente
            if ($dav->id !== $ultimaDav->id) {
                return back()->with('error', 'Só é permitido retificar a versão mais recente da DAV.');
            }

            // Bloquear se já existir filha pendente
            $existePendente = Dav::where(function ($q) use ($raizId) {
                    $q->where('id', $raizId)
                    ->orWhere('dav_pai_id', $raizId);
                })
                ->where('status', 'Pendente')
                ->exists();

            if ($existePendente) {
                return back()->with('error', 'Já existe uma retificação pendente para esta DAV.');
            }

            // Carregar relações
            $dav->load('profissionais.trechos');

            // ===============================
            // 1️⃣ DEVOLVER SALDO DA REPROVADA
            // ===============================

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

            DavQuantidade::where('tipo','diarias')
                ->increment('quantidade_atual', $totalDiarias);

            DavQuantidade::where('tipo','passagem_aerea')
                ->increment('quantidade_atual', $totalAereo);

            DavQuantidade::where('tipo','veiculo_aquatico')
                ->increment('quantidade_atual', $totalAquatico);

            DavQuantidade::where('tipo','veiculo_hatch')
                ->increment('quantidade_atual', $totalHatch);

            DavQuantidade::where('tipo','veiculo_pickup')
                ->increment('quantidade_atual', $totalPickup);

            // ===============================
            // 2️⃣ CALCULAR NOVA VERSÃO
            // ===============================

            $ultimaVersao = Dav::where(function ($q) use ($raizId) {
                    $q->where('id', $raizId)
                    ->orWhere('dav_pai_id', $raizId);
                })
                ->max('versao');

            $novaVersao = $ultimaVersao + 1;

            // ===============================
            // 3️⃣ CRIAR NOVA DAV
            // ===============================

            $novaDav = Dav::create([
                'coordenador' => $dav->coordenador,
                'empreendimento_id' => $dav->empreendimento_id,
                'n_ose' => $dav->n_ose,
                'n_sei_ose' => $dav->n_sei_ose,
                'produto' => $dav->produto,
                'subproduto' => $dav->subproduto,
                'fiscal_nome' => $fiscalAtual['nome'],
                'fiscal_cargo' => $fiscalAtual['cargo'],
                'versao' => $novaVersao,
                'dav_pai_id' => $raizId,
                'status' => 'Pendente',
                'motivo_reprovacao' => null,
                'aprovado_por' => null,
                'aprovado_em' => null,
            ]);

            // ===============================
            // 4️⃣ COPIAR PROFISSIONAIS
            // ===============================

            foreach ($dav->profissionais as $prof) {

                $novoProf = DavProfissionalDocumento::create([
                    'dav_id' => $novaDav->id,
                    'profissional_id' => $prof->profissional_id,
                    'funcao' => $prof->funcao,
                    'desc_trecho' => $prof->desc_trecho,
                    'data_ini' => $prof->data_ini,
                    'data_fim' => $prof->data_fim,
                    'diarias' => $prof->diarias,
                ]);

                foreach ($prof->trechos as $trecho) {

                    DavProfissionalTrecho::create([
                        'dav_profissional_documento_id' => $novoProf->id,
                        'origem' => $trecho->origem,
                        'destino' => $trecho->destino,
                        'aereo_qtd' => $trecho->aereo_qtd,
                        'aquatico_qtd' => $trecho->aquatico_qtd,
                        'terrestre_pickup_qtd' => $trecho->terrestre_pickup_qtd,
                        'terrestre_hatch_qtd' => $trecho->terrestre_hatch_qtd,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('dav.edit', $novaDav->id)
                ->with('success', 'Retificação criada. Atualize e envie novamente.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors($e->getMessage());
        }
    }

    public function edit(Dav $dav)
    {
        if ($dav->status !== 'Pendente') {
            return back()->with('error', 'Só é possível editar DAV pendente.');
        }

        $dav->load([
            'profissionais.trechos',
            'empreendimento'
        ]);

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

        return Inertia::render('Dav/Edit', [
            'dav' => $dav,
            'empreendimentos' => $empreendimentos,
            'produtos' => $produtos,
            'profissionais' => $profissionais,
            'auth' => [
                'user' => auth()->user()
            ]
        ]);
    }

    public function update(Request $request, Dav $dav)
    {
        DB::beginTransaction();

        try {

            if ($dav->status !== 'Pendente') {
                return back()->with('error', 'Só é possível atualizar DAV pendente.');
            }

            // ===============================
            // 1️⃣ Atualizar dados principais
            // ===============================

            $dav->update([
                'coordenador' => $request->coordenador,
                'empreendimento_id' => $request->empreendimento_id,
                'n_ose' => $request->ose,
                'n_sei_ose' => $request->n_sei_ose,
                'produto' => $request->produto,
                'subproduto' => $request->subproduto,
            ]);

            // ===============================
            // 2️⃣ Apagar vínculos antigos
            // ===============================

            foreach ($dav->profissionais as $prof) {
                $prof->trechos()->delete();
                $prof->delete();
            }

            // ===============================
            // 3️⃣ Recriar profissionais e trechos
            // ===============================

            foreach ($request->profissionais as $item) {

                $profissionalDocumento = DavProfissionalDocumento::create([
                    'dav_id' => $dav->id,
                    'profissional_id' => $item['profissional_id'],
                    'funcao' => $item['funcao'],
                    'desc_trecho' => $item['desc_trecho'] ?? null,
                    'data_ini' => $item['data_ini'],
                    'data_fim' => $item['data_fim'],
                    'diarias' => $item['diarias'] ?? 0,
                ]);

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
            // 4️⃣ Recalcular totais
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
            // 5️⃣ Descontar novamente
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
            // 6️⃣ Atualizar snapshot
            // ===============================

            $saldos = DavQuantidade::pluck('quantidade_atual', 'tipo');

            $dav->update([
                'diarias_total' => $saldos['diarias'] ?? 0,
                'aereo_total' => $saldos['passagem_aerea'] ?? 0,
                'aquatico_total' => $saldos['veiculo_aquatico'] ?? 0,
                'hatch_total' => $saldos['veiculo_hatch'] ?? 0,
                'pickup_total' => $saldos['veiculo_pickup'] ?? 0,
            ]);

            DB::commit();

            // 📧 Notificar via email
            $emails = [
                'rhuan.borges@jgpconsultoria.com.br',
                'alberto.maeda@dnit.gov.br'
            ];

            foreach ($emails as $email) {
                Mail::to($email)->send(new DavStatusMail($dav));
            }

            return redirect()->route('dav.show', $dav->id)
                ->with('success', 'DAV atualizada com sucesso.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors($e->getMessage());
        }
    }
    

}