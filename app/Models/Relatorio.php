<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relatorio extends Model
{
    protected $table = 'relatorios';

    protected $fillable = [
        'numero',
        'ativo',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
}
