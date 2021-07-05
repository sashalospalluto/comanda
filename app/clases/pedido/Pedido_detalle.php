<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido_detalle extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'pedidos_detalle';
    public $incrementing = true;
    public $timestamps = false;

    const DELETED_AT = 'fechaBaja';

    protected $fillable = [
		'id_pedido_general',
        'id_producto_pedido',
        'id_empleado_asignado',
        'estado',
        'tiempo_de_preparacion',
        'hora_inicio_preparacion',
        'hora_fin_preparacion',
        'fechaBaja'        
    ];
}

?>