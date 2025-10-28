<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Oficio extends Model
{
    use SoftDeletes;

    protected $table = 'registros_oficios';

    protected $fillable = [
        'contador',
        'rodovia',
        'data_registro',
        'oficio_num',
        'assunto',
        'texto',
        'meio',
        'autor',
    ];

    protected $dates = [
        'data_registro',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function autorUsuario()
    {
        return $this->belongsTo(User::class, 'autor');
    }
}
