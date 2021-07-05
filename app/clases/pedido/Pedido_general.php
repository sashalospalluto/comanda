<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido_general extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'pedidos_general';
    public $incrementing = true;
    public $timestamps = false;

    const DELETED_AT = 'fechaBaja';

    protected $fillable = [
		'id_mesa', 'id_mozo', 'nombre_cliente', 'hora_ingreso', 'hora_egreso', 'total', 'promedio_puntuacion',	'fechaBaja'	
    ];
}

?>