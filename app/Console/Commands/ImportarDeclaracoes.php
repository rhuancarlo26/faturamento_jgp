<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Relatorio;
use App\Models\Documento;
use App\Models\Profissional;

class ImportarDeclaracoes extends Command
{
    protected $signature = 'importar:declaracoes {path}';

    protected $description = 'Importa PDFs de declarações por relatório';

    public function handle()
    {
        $basePath = $this->argument('path');

        if (!File::exists($basePath)) {
            $this->error("Pasta não encontrada: {$basePath}");
            return;
        }

        /**
         * Mapa de aliases (nome no PDF => nome no banco)
         */
        $aliases = [
            'Jose Carlos' => 'Jose Carlos Pereira',
            'Joubert' => 'Joubert de Oliveira',
            'Rhuan Carlo' => 'Rhuan Carlo Borges',
            'Carolina Sampaio' => 'Carolina Frambach',
        ];

        $pastas = File::directories($basePath);

        foreach ($pastas as $pasta) {

            // Extrai número do relatório (ex: 6º)
            if (!preg_match('/(\d+)[ºo]?/u', basename($pasta), $match)) {
                $this->warn("Não consegui identificar o relatório em {$pasta}");
                continue;
            }

            $numeroRelatorio = (int) $match[1];

            $relatorio = Relatorio::firstOrCreate(
                ['numero' => $numeroRelatorio],
                ['ativo' => 0]
            );

            $arquivos = File::files($pasta);

            foreach ($arquivos as $arquivo) {

                if ($arquivo->getExtension() !== 'pdf') {
                    continue;
                }

                $nomeOriginal = $arquivo->getFilename();

                // Limpa nome
                $nomeLimpo = str_replace(['Declaração', '.pdf'], '', $nomeOriginal);
                $nomeLimpo = trim(preg_replace('/\s+/', ' ', $nomeLimpo));

                // Aplica alias se existir
                if (isset($aliases[$nomeLimpo])) {
                    $nomeLimpo = $aliases[$nomeLimpo];
                }

                $profissional = Profissional::where('nome', $nomeLimpo)->first();

                if (!$profissional) {
                    $this->error("❌ Profissional não encontrado: {$nomeLimpo} (arquivo: {$nomeOriginal})");
                    continue;
                }

                // Move arquivo para storage
                $novoNome = Str::random(40) . '.pdf';
                $destino = storage_path('app/public/documentos/' . $novoNome);

                File::copy($arquivo->getRealPath(), $destino);

                Documento::create([
                    'profissional_id' => $profissional->id,
                    'relatorio_id' => $relatorio->id,
                    'arquivo' => 'documentos/' . $novoNome,
                ]);

                $this->info("✔ Importado: {$nomeOriginal}");
            }
        }

        // Marca o maior relatório como ativo
        Relatorio::query()->update(['ativo' => 0]);

        Relatorio::orderBy('numero', 'desc')
            ->first()
            ?->update(['ativo' => 1]);

        $this->info('🚀 Importação finalizada com sucesso!');
    }
}
