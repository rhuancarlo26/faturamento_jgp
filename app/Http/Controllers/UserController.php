<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'master') {
            abort(403);
        }

        return Inertia::render('Usuarios/Index', [
            'users' => User::select('id', 'name', 'email', 'role')->get(),
            'user' => auth()->user()

        ]);
        
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'master') {
            abort(403);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'Usuário criado com sucesso');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($id);

        // Apenas master pode excluir
        if ($authUser->role !== 'master') {
            return back()->withErrors([
                'error' => 'Apenas o master pode excluir usuários.'
            ]);
        }

        // Impedir excluir a si mesmo (boa prática)
        if ($authUser->id === $user->id) {
            return back()->withErrors([
                'error' => 'Você não pode excluir seu próprio usuário.'
            ]);
        }

        $user->delete();

        return back()->with('success', 'Usuário removido com sucesso.');
    }

    public function formSenha()
    {
        return Inertia::render('Usuarios/AlterarSenha', [
            'user' => auth()->user()
        ]);
        
    }

    public function alterarSenha(Request $request)
    {
        $request->validate(
            [
                'senha_atual' => ['required'],
                'nova_senha' => ['required', 'min:6', 'confirmed'],
            ],
            [
                'senha_atual.required' => 'Informe a senha atual.',
                'nova_senha.required' => 'Informe a nova senha.',
                'nova_senha.min' => 'A nova senha deve ter no mínimo 6 caracteres.',
                'nova_senha.confirmed' => 'Os campos de nova senha e confirmação de senha devem ser iguais.',
            ]
        );

        if (!Hash::check($request->senha_atual, auth()->user()->password)) {
            return back()->withErrors([
                'senha_atual' => 'A senha atual está incorreta.'
            ]);
        }

        auth()->user()->update([
            'password' => Hash::make($request->nova_senha)
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

}

