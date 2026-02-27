<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empreendimento extends Model
{
    use SoftDeletes;

    protected $table = 'empreendimentos';

    protected $fillable = [
        'cod_emp',
        'ose_emp'
    ];
}