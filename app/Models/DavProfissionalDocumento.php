<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DavProfissionalDocumento extends Model
{
    protected $table = 'dav_profissionais_documento';

    protected $fillable = [
        'dav_id',
        'profissional_id',
        'funcao',
        'desc_trecho',
        'data_ini',
        'data_fim',
        'diarias'
    ];

    // 🔗 Pertence a um DAV
    public function dav()
    {
        return $this->belongsTo(Dav::class, 'dav_id');
    }

    // 🔗 Pertence a um profissional
    public function profissional()
    {
        return $this->belongsTo(DavProfissionais::class, 'profissional_id');
    }

    public function trechos()
    {
        return $this->hasMany(DavProfissionalTrecho::class,'dav_profissional_documento_id');
    }
}