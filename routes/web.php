<?php
use App\Http\Controllers\SubprodutoController;
use Illuminate\Support\Facades\Route;

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

Auth::routes();

