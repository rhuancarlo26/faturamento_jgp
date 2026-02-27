<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DavQuantidade extends Model
{
    protected $table = 'dav_quantidades';

    protected $fillable = [
        'tipo',
        'quantidade_atual'
    ];

    public $timestamps = false; 
}