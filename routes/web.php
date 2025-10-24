<?php
use App\Http\Controllers\SubprodutoController;
use App\Http\Controllers\OficiosController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [SubprodutoController::class, 'index'])->name('subprodutos.index');
Route::get('/subprodutos', [SubprodutoController::class, 'index'])->name('subprodutos.index');
Route::get('/subprodutos/fetch/{subproduto}', [SubprodutoController::class, 'fetch'])->name('subprodutos.fetch');

Route::middleware('auth')->group(function () {
    Route::get('/subprodutos/create', [SubprodutoController::class, 'create'])->name('subprodutos.create');
    Route::post('/subprodutos', [SubprodutoController::class, 'store'])->name('subprodutos.store');
    Route::delete('/subprodutos/{id}', [SubprodutoController::class, 'destroy'])->name('subprodutos.destroy');
    Route::get('/subprodutos/{id}/edit', [SubprodutoController::class, 'edit'])->name('subprodutos.edit');
    Route::put('/subprodutos/{id}', [SubprodutoController::class, 'update'])->name('subprodutos.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/oficios', [OficiosController::class, 'index'])->name('oficios.index');
    Route::post('/oficios', [OficiosController::class, 'store'])->name('oficios.store');
    Route::get('/oficios/{id}', [OficiosController::class, 'show'])->name('oficios.show');
    Route::get('/oficios-lista', [OficiosController::class, 'listar'])->name('oficios.listar');
});

Route::middleware('auth')->get('/oficios', function () {
    return Inertia::render('Oficios/Index', [
        'user' => auth()->user(),
    ]);
})->name('oficios.index');

Auth::routes();

