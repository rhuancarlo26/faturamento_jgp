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
        $subprodutoOptions = Subproduto::all(['subproduto', 'cod_siac', 'descricao_revisada']);
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
        ]);

        RegistroSubproduto::create(array_merge($request->all(), ['id_user' => auth()->id()]));
        return redirect()->route('subprodutos.index')->with('success', 'Subproduto cadastrado!');
    }

    public function destroy($id)
    {
        $subproduto = RegistroSubproduto::findOrFail($id);
        $subproduto->id_user = auth()->id(); // Preenche id_user
        $subproduto->save();
        $subproduto->delete(); // Ativa soft delete
        return redirect()->route('subprodutos.index')->with('success', 'Subproduto excluído!');
    }

    public function fetch($subproduto)
    {
        $subprodutoData = Subproduto::where('subproduto', $subproduto)->first();
        \Log::info('Subproduto buscado:', ['subproduto' => $subproduto, 'data' => $subprodutoData]);
        return response()->json($subprodutoData ? [
            'subproduto' => $subprodutoData->subproduto . ' - ' . $subprodutoData->descricao_revisada,
            'cod_siac' => $subprodutoData->cod_siac,
        ] : ['subproduto' => '', 'cod_siac' => '']);
    }
}