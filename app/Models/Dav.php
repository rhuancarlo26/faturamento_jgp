<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dav extends Model
{
    protected $table = 'dav';

    protected $fillable = [
        'coordenador',
        'empreendimento_id',
        'n_ose',
        'n_sei_ose',
        'produto',
        'subproduto',
        'fiscal_nome',
        'fiscal_cargo',
        'status',
        'motivo_reprovacao',
        'aprovado_por',
        'aprovado_em',
        'versao',
        'dav_pai_id',
        'diarias_total', 
        'aereo_total', 
        'aquatico_total', 
        'hatch_total', 
        'pickup_total'
    ];

    // 🔗 Um DAV tem vários profissionais vinculados
    public function profissionais()
    {
        return $this->hasMany(DavProfissionalDocumento::class, 'dav_id');
    }

    // 🔗 Relacionamento com empreendimento
    public function empreendimento()
    {
        return $this->belongsTo(Empreendimento::class, 'empreendimento_id');
    }

    public function aprovador()
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

}