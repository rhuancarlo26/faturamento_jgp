<?php

use App\Http\Controllers\SubprodutoController;
use App\Http\Controllers\OficiosController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\RelatoriosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DavController;


    Route::middleware(['auth'])->group(function () {

        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::put('/usuarios/{id}', [UserController::class, 'update']);
        Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

        Route::get('/alterar-senha', [UserController::class, 'formSenha']);
        Route::post('/alterar-senha', [UserController::class, 'alterarSenha']);

        Route::put('/usuarios/{id}', [UserController::class, 'update']);


    });

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

    Route::middleware('auth')->group(function () {
        Route::get('/documentos', [DocumentosController::class, 'index']);
        Route::post('/documentos', [DocumentosController::class, 'store']);
        Route::get('/documentos/{id}/download', [DocumentosController::class, 'download'])
            ->whereNumber('id')
            ->name('documentos.download');

        Route::delete('/documentos/{id}', [DocumentosController::class, 'destroy'])
            ->whereNumber('id')
            ->name('documentos.destroy');

        Route::get('/documentos/{id}/visualizar', [DocumentosController::class, 'visualizar'])
            ->whereNumber('id')
            ->name('documentos.visualizar');

        Route::post('/relatorios/gerar-novo', [RelatoriosController::class, 'gerarNovo'])
            ->name('relatorios.gerar-novo');
    });


    Route::middleware(['auth'])->group(function () {

        Route::get('/dav', [DavController::class, 'index'])->name('dav.index');
        Route::get('/dav/create', [DavController::class, 'create'])->name('dav.create');
        Route::post('/dav', [DavController::class, 'store'])->name('dav.store');
        Route::get('/dav/subprodutos', [DavController::class, 'buscarSubprodutos'])->name('dav.subprodutos');
        Route::post('/dav/profissionais', [DavController::class, 'storeProfissional']);
        Route::get('/dav/{dav}', [DavController::class, 'show'])->name('dav.show');

        Route::get('/dav/{dav}/download', [DavController::class, 'download'])->name('dav.download');
    });



Auth::routes();
