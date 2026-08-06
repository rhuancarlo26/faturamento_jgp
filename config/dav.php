<?php

return [
    'fiscal' => [
        'nome' => env('DAV_FISCAL_NOME', 'Alberto Yoshikasu Maeda'),
        'cargo' => env('DAV_FISCAL_CARGO', 'Coordenador de Estudos e Projetos Ambientais'),
        // Data de virada para fallback de PDFs legados sem snapshot gravado.
        'data_troca' => env('DAV_FISCAL_DATA_TROCA', '2026-07-14'),
    ],
];
