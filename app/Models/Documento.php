<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'profissional_id',
        'relatorio_id',
        'arquivo',
    ];

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }

    public function relatorio()
    {
        return $this->belongsTo(Relatorio::class);
    }
}
