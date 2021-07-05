<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encuesta extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'encuestas';
    public $incrementing = true;
    public $timestamps = false;

    const DELETED_AT = 'fechaBaja';

    protected $fillable = [
        'id_mesa',	'id_pedidos_general',	'mesa',	'restaurante',	'mozo',	'cocinero',	'experiencia',	'fechaBaja'
    ];
}

?>