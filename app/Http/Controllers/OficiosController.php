<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Oficio;

class OficiosController extends Controller
{
    public function index()
    {
        return inertia('Oficios/Index', [
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rodovia' => 'required',
            'data_oficio' => 'required|date',
            'oficio_num' => 'required|string|max:255',
            'assunto' => 'required',
            'texto_oficio' => 'required',
        ]);

        Oficio::create([
            'rodovia' => $request->rodovia,
            'data_registro' => now(),
            'oficio_num' => $request->oficio_num,
            'assunto' => $request->assunto,
            'texto' => $request->texto_oficio,
            'autor' => auth()->id(),
        ]);

        return redirect()->route('oficios.index')->with('success', 'Ofício registrado com sucesso!');
    }
    
    public function listar()
    {
        $oficios = DB::table('registros_oficios')
            ->select('id', 'oficio_num', 'assunto', 'texto')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

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
}
