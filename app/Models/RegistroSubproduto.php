<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistroSubproduto extends Model
{
    use SoftDeletes;

    protected $table = 'registros_subprodutos';

    protected $fillable = [
        'rodovia',
        'data_aprovacao',
        'sei',
        'oficio_numero',
        'sei_versao_aprovada',
        'subproduto',
        'cod_siac',
        'id_user'
    ];

    protected $dates = ['deleted_at'];
}