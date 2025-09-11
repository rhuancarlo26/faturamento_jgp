<?php
namespace App\Http\Controllers;
use App\Models\RegistroSubproduto;
use App\Models\Subproduto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubprodutoController extends Controller
{
    public function index(Request $request)
    {
        $query = RegistroSubproduto::query()->whereNull('deleted_at');
        if ($request->data_inicial && $request->data_final) {
            $query->whereBetween('data_aprovacao', [$request->data_inicial, $request->data_final]);
        }
        return Inertia::render('Subprodutos/Index', [
            'subprodutos' => $query->get(),
            'dataInicial' => $request->data_inicial,
            'dataFinal' => $request->data_final,
            'user' => auth()->user(),
        ]);
    }

    public function create()
    {
        $subprodutoOptions = Subproduto::all(['id', 'subproduto', 'cod_siac', 'unidade_de_medida', 'descricao_revisada']);
        return Inertia::render('Subprodutos/Create', [
            'subprodutoOptions' => $subprodutoOptions,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rodovia' => 'required',
            'data_aprovacao' => 'required|date',
            'sei' => 'nullable',
            'oficio_numero' => 'nullable',
            'sei_versao_aprovada' => 'nullable',
            'subproduto' => 'required',
            'cod_siac' => 'nullable',
            'quantidade' => 'required|integer|min:1',
            'unidade' => 'nullable',
            'quantidade_medida' => 'nullable|numeric',
        ]);

        $subprodutoData = Subproduto::where('subproduto', $request->subproduto)->first();
        $data = array_merge($request->all(), [
            'id_subproduto' => $request->subproduto, // Mantém o valor de subproduto (ex.: "1.1.1")
            'subproduto' => $subprodutoData->descricao_revisada ?? '', // Popula com descricao_revisada
            'cod_siac' => $subprodutoData->cod_siac ?? $request->cod_siac,
            'unidade' => $subprodutoData->unidade_de_medida ?? $request->unidade,
            'id_user' => auth()->id(),
        ]);

        RegistroSubproduto::create($data);
        return redirect()->route('subprodutos.index')->with('success', 'Subproduto cadastrado!');
    }

    public function destroy($id)
    {
        $subproduto = RegistroSubproduto::findOrFail($id);
        $subproduto->id_user = auth()->id();
        $subproduto->save();
        $subproduto->delete();
        return redirect()->route('subprodutos.index')->with('success', 'Subproduto excluído!');
    }

    public function edit($id)
    {
        $subproduto = RegistroSubproduto::findOrFail($id);
        $subprodutoOptions = Subproduto::all(['id', 'subproduto', 'cod_siac', 'unidade_de_medida', 'descricao_revisada']);
        return Inertia::render('Subprodutos/Edit', [
            'subproduto' => $subproduto,
            'subprodutoOptions' => $subprodutoOptions,
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $subproduto = RegistroSubproduto::findOrFail($id);
        $request->validate([
            'rodovia' => 'required',
            'data_aprovacao' => 'required|date',
            'sei' => 'nullable',
            'oficio_numero' => 'nullable',
            'sei_versao_aprovada' => 'nullable',
            'subproduto' => 'required',
            'cod_siac' => 'nullable',
            'quantidade' => 'required|integer|min:1',
            'unidade' => 'nullable',
            'quantidade_medida' => 'nullable|numeric',
        ]);

        $subprodutoData = Subproduto::where('subproduto', $request->subproduto)->first();
        $data = array_merge($request->all(), [
            'id_subproduto' => $request->subproduto, // Mantém o valor de subproduto
            'subproduto' => $subprodutoData->descricao_revisada ?? $subproduto->subproduto, // Atualiza com descricao_revisada
            'cod_siac' => $subprodutoData->cod_siac ?? $request->cod_siac,
            'unidade' => $subprodutoData->unidade_de_medida ?? $request->unidade,
        ]);

        $subproduto->update($data);
        return redirect()->route('subprodutos.index')->with('success', 'Subproduto atualizado!');
    }

    public function fetch($subproduto)
    {
        $subprodutoData = Subproduto::where('subproduto', $subproduto)->first();
        \Log::info('Subproduto buscado:', ['subproduto' => $subproduto, 'data' => $subprodutoData]);
        return response()->json($subprodutoData ? [
            'subproduto' => $subprodutoData->subproduto . ' - ' . $subprodutoData->descricao_revisada,
            'cod_siac' => $subprodutoData->cod_siac,
            'unidade_de_medida' => $subprodutoData->unidade_de_medida,
        ] : ['subproduto' => '', 'cod_siac' => '', 'unidade_de_medida' => '']);
    }
}