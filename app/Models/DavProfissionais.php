<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DavProfissionais extends Model
{
    protected $fillable = [
        'nome', 
        'formacao'
    ];

    public function documentos()
    {
        return $this->hasMany(DavProfissionalDocumento::class, 'profissional_id');
    }

    public function trechos()
    {
        return $this->hasMany(DavProfissionalTrecho::class, 'dav_profissional_id');
    }
}
