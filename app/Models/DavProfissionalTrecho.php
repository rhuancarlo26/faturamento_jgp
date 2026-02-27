<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DavProfissionalTrecho extends Model
{
    protected $table = 'dav_profissional_trechos';

    protected $fillable = [
        'dav_profissional_documento_id',
        'origem',
        'destino',
        'aereo_qtd',
        'aquatico_qtd',
        'terrestre_pickup_qtd',
        'terrestre_hatch_qtd'
    ];

    public function profissionalDocumento()
    {
        return $this->belongsTo(DavProfissionalDocumento::class, 'dav_profissional_documento_id');
    }
}