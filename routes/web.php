<?php

use App\Http\Controllers\SubprodutoController;
use App\Http\Controllers\OficiosController;
use Illuminate\Support\Facades\Route;


// Home & Subprodutos
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


// Ofícios
Route::middleware('auth')->group(function () {
    Route::get('/oficios', [OficiosController::class, 'index'])->name('oficios.index');
    Route::post('/oficios', [OficiosController::class, 'store'])->name('oficios.store');

    Route::get('/oficios/ultimo-contador', [OficiosController::class, 'ultimoContador'])->name('oficios.ultimo_contador');
    Route::get('/oficios-listar', [OficiosController::class, 'listarView'])->name('oficios.listar.view');
    Route::get('/oficios-lista', [OficiosController::class, 'listar'])->name('oficios.listar');

    Route::get('/oficios/{id}/download', [OficiosController::class, 'download'])
        ->whereNumber('id')
        ->name('oficios.download');

    Route::get('/oficios/{id}', [OficiosController::class, 'show'])
        ->whereNumber('id')
        ->name('oficios.show');

    Route::get('/oficios/pdf/{id}', [OficiosController::class, 'gerarPdf'])->name('oficios.pdf');

    Route::put('/oficios/{id}', [OficiosController::class, 'update'])->whereNumber('id')->name('oficios.update');


    Route::post('/oficios/{id}/upload-final', [OficiosController::class, 'uploadArquivoPersonalizado'])
    ->name('oficios.uploadFinal');

    Route::delete('/oficios/{id}/arquivo-personalizado', [OficiosController::class, 'removerArquivoPersonalizado'])
    ->name('oficios.removerArquivo');




});

Auth::routes();
