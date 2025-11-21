<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrosOficio extends Model
{
    protected $fillable = [
        'id',
        'contador',
        'rodovia',
        'data_registro',
        'oficio_num',
        'assunto',
        'texto',
        'autor',
        'arquivo_personalizado'
    ];
}
