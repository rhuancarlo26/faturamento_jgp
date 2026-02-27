<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profissional extends Model
{
    protected $table = 'profissionais';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
}
