<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subproduto extends Model
{
    protected $fillable = [
        'id',
        'cod_siac', 
        'produto', 
        'subproduto', 
        'descricao_siac', 
        'descricao_revisada' 
    ];
}


