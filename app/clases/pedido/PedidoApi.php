<?php
require_once 'Pedido_detalle.php';
require_once 'Pedido_general.php';
require_once '../app/interfaces/IApiUsable.php';
require '../pdf.php';


use \App\Models\Pedido_general as Pedido_general;
use \App\Models\Pedido_detalle as Pedido_detalle;
use \App\Models\Producto as Producto;
use \App\Models\Usuario as Usuario;
use \App\Models\Mesa as Mesa;

class PedidoApi implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        //$ArrayDeParametros = $request->getParsedBody();
        $id_pedido = $args['id_pedido'];
        $id_mesa = $args['id_mesa'];

        $pedido = Pedido_general::find($id_pedido);
        $lista = [];

        if($pedido->id_mesa == $id_mesa)
        {
            /* $lista = Pedido_general::select(
                'pedidos_general.id_mesa',
                'pedidos_general.id_mozo',
                'pedidos_general.nombre_cliente',
                'pedidos_general.hora_ingreso',
                'pedidos_general.hora_egreso',
                'pedidos_general.total',
                'pedidos_general.promedio_puntuacion',
                'pedidos_detalle.id_producto_pedido',
                'pedidos_detalle.id_empleado_asignado',
                'pedidos_detalle.estado',
                'pedidos_detalle.tiempo_de_preparacion',
                'pedidos_detalle.hora_inicio_preparacion',
                'pedidos_detalle.hora_fin_preparacion'
            )
                ->join('pedidos_detalle', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')
                ->where('pedidos_general.id', '=', $id_pedido)
                ->where('pedidos_general.id_mesa', '=', $pedido->id_mesa)->get(); */

            $pedido_detalle = Pedido_detalle :: where('id_pedido_general','=', $id_pedido)->get();
            
            foreach ($pedido_detalle as $miPedido)
            {
                $unPedidoConDetalle = [];

                if($miPedido->hora_inicio_preparacion != null)//si el pedido ya esta en preparacion
                {
                    if($miPedido->hora_fin_preparacion == null) //si la preparacion no finalizo
                    {
                        $unPedidoConDetalle[] = Pedido_general::select(
                            'pedidos_general.id_mesa',
                            'pedidos_general.id_mozo',
                            'pedidos_general.nombre_cliente',
                            'pedidos_detalle.id_producto_pedido',
                            'pedidos_detalle.id_empleado_asignado',
                            'pedidos_detalle.estado',
                            'pedidos_detalle.tiempo_de_preparacion',
                            'pedidos_detalle.hora_inicio_preparacion',
                        )
                            ->join('pedidos_detalle', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')
                            ->where('pedidos_general.id', '=', $miPedido->id_pedido_general)
                            ->where('pedidos_general.id_mesa', '=', $pedido->id_mesa)
                            ->where('pedidos_detalle.id', '=',$miPedido->id)->get();

                        $fecha1 = new DateTime();//fecha
                        $fecha2 = new DateTime();//fecha

                        $tiempoDePreparacion = "+".$miPedido->tiempo_de_preparacion." minute";
                        
                        $horaEstimadaTerminado = strtotime ( $tiempoDePreparacion , strtotime($miPedido->hora_inicio_preparacion));
                        $horaEstimadaTerminado = date ('Y-m-d H:i:s', $horaEstimadaTerminado);  
                        $formato = 'Y-m-d H:i:s';
                        $fecha2 = DateTime::createFromFormat($formato, $horaEstimadaTerminado,null);
                                                
                        $intervalo = date_diff($fecha1,$fecha2);

                        if($fecha1>=$fecha2)
                        {
                            $unPedidoConDetalle[] = 'Su pedido ya deberia estar listo, en breve un mozo lo llevará';
                        }
                        else
                        {
                            $unPedidoConDetalle[] = $intervalo->format('Tiempo restante para su pedido: %i minutos ');
                        }
                    }
                    else
                    {
                        $unPedidoConDetalle[] = Pedido_general::select(
                            'pedidos_general.id_mesa',
                            'pedidos_general.id_mozo',
                            'pedidos_general.nombre_cliente',
                            'pedidos_detalle.id_producto_pedido',
                            'pedidos_detalle.id_empleado_asignado',
                            'pedidos_detalle.estado',
                            'pedidos_detalle.tiempo_de_preparacion',
                            'pedidos_detalle.hora_inicio_preparacion',
                            'pedidos_detalle.hora_fin_preparacion',
                        )
                            ->join('pedidos_detalle', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')
                            ->where('pedidos_general.id', '=', $miPedido->id_pedido_general)
                            ->where('pedidos_general.id_mesa', '=', $pedido->id_mesa)
                            ->where('pedidos_detalle.id', '=',$miPedido->id)->get();
                        $unPedidoConDetalle[]= 'Su pedido ya esta listo, en breve un mozo lo llevará';
                    }
                   
                }
                else
                {
                    $unPedidoConDetalle[] = Pedido_general::select(
                        'pedidos_general.id_mesa',
                        'pedidos_general.id_mozo',
                        'pedidos_general.nombre_cliente',
                        'pedidos_detalle.id_producto_pedido',
                        'pedidos_detalle.id_empleado_asignado',
                        'pedidos_detalle.estado',
                    )
                        ->join('pedidos_detalle', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')
                        ->where('pedidos_general.id', '=', $miPedido->id_pedido_general)
                        ->where('pedidos_general.id_mesa', '=', $pedido->id_mesa)
                        ->where('pedidos_detalle.id', '=',$miPedido->id)->get();
                    $unPedidoConDetalle[]= 'Su pedido esta proximo a asignarse a uno de nuestros empleados, aguarde unos minutos y vuelva a consultar';

                    
                }
                
                $lista[]= $unPedidoConDetalle;
            }

            if ($lista != null) 
            {
                $payload = json_encode($lista);
            } 
            else 
            {
                $payload = json_encode(array("mensaje" => "id no encontrado"));
            } 
        }
        else
        {
            $payload = json_encode(array("mensaje" => "datos no encontrados"));
        }


        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Pedido_detalle::select(
            'pedidos_detalle.*',
            'pedidos_general.id_mozo',
            'pedidos_general.nombre_cliente',
            'pedidos_general.hora_ingreso',
            'pedidos_general.hora_egreso',
            'pedidos_general.total',
            'pedidos_general.promedio_puntuacion'
        )
            ->join('pedidos_general', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')->get();

        /* $lista = Pedido_general::select('pedidos_general.id_mesa',
        'pedidos_general.id_mozo', 
        'usuarios.nombre as mozoAsignado',
        'pedidos_general.nombre_cliente', 
        'pedidos_general.hora_ingreso', 
        'pedidos_general.hora_egreso', 
        'pedidos_general.total', 
        'pedidos_general.promedio_puntuacion',	
        'productos.nombre as producto',
        'pedidos_detalle.id_empleado_asignado',
        'pedidos_detalle.estado',
        'pedidos_detalle.tiempo_de_preparacion',
        'pedidos_detalle.hora_inicio_preparacion',
        'pedidos_detalle.hora_fin_preparacion')
        ->join('pedidos_detalle', 'pedidos_detalle.id_pedido_general', '=', 'pedidos_general.id')
        ->join('productos', 'productos.id', '=', "pedidos_detalle.id_producto_pedido")
        ->join('usuarios', 'usuarios.id', '=', "pedidos_general.id_mozo")->get(); */

        $payload = json_encode(array("Pedidos" => $lista));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarUno($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();

        $id_mesa = $ArrayDeParametros['id_mesa'];
        $id_mozo = $ArrayDeParametros['id_mozo'];
        $nombre_cliente = $ArrayDeParametros['nombre_cliente'];
        $id_producto_pedido = $ArrayDeParametros['id_producto_pedido'];

        $nuevoPedido = new Pedido_general();

        if (isset($id_mesa, $id_mozo, $nombre_cliente, $id_producto_pedido) && !empty($id_mesa) && !empty($id_mozo) && !empty($nombre_cliente) && !empty($id_producto_pedido)) 
        {
            $mesaAVerificar = Mesa::find($id_mesa);
            $mozoAVerificar = Usuario::find($id_mozo);

            $pedido = Pedido_general::where('id_mesa', '=', $id_mesa)
                ->where('id_mozo', '=', $id_mozo)
                ->where('nombre_cliente', '=', $nombre_cliente)
                ->where('hora_egreso', '=', null)->first();
            
            $producto = Producto :: find($id_producto_pedido);
            
            if($mozoAVerificar!=null)
            {
                if($mozoAVerificar->tipo == 'mozo')
                {
                    if($producto!=null) //verifico si el producto existe
                    { 
                        if($producto->stock>=1) //si el producto tiene stock
                        {
                            if ($pedido == null)  //Verifico si la mesa ya existe (le agrego un pedido mas con el mismo num de id que ya se le dio) o es una mesa nueva 
                            {
                                if($mesaAVerificar->estado == 'cerrada') //verifico si la mesa que se quiere usar, esta libre
                                {
                                    $nuevoPedido->id_mesa = $id_mesa;
                                    $nuevoPedido->id_mozo = $id_mozo;
                                    $nuevoPedido->nombre_cliente = $nombre_cliente;
                                    $nuevoPedido->hora_ingreso = date('Y-m-d H:i:s');
                                    
                                    if ($nuevoPedido->save()) 
                                    {
                                        $pedidoParaPreparar = new Pedido_detalle();
                                        
                                        $pedidoParaPreparar->id_pedido_general = $nuevoPedido->id;
                                        $pedidoParaPreparar->id_producto_pedido = $id_producto_pedido;
                                        $pedidoParaPreparar->estado = 'pendiente';
                                        $pedidoParaPreparar->save();
                                        
                                        $mesaAVerificar->estado='Cliente esperando pedido';
                                        $mesaAVerificar->save();
                                        
                                        $producto->stock -= 1;
                                        $producto->save();

                                        if(isset($_FILES['imagen']) && !empty($_FILES['imagen']))
                                        {
                                            $imagen = $_FILES['imagen'];
                                            $extension = pathinfo($imagen['name'],PATHINFO_EXTENSION);
                                            //muevo la imagen a mi carpeta
                                            move_uploaded_file($imagen['tmp_name'],"../app/Fotos/$id_mesa-$pedidoParaPreparar->id.$extension");
                                        }
                                        
                                        $payload = json_encode(array("mensaje" => "Pedido creado con exito"));
                                    } 
                                    else 
                                    {
                                        $payload = json_encode(array("mensaje" => "Error al crear pedido"));
                                    }                        
                                }
                                else
                                {
                                    $payload = json_encode(array("mensaje" => "La mesa esta en uso"));
                                }
                            } 
                            else 
                            {
                                $payload = json_encode(array("mensaje" => "Ya existe un pedido con el mismo mozo, cliente y mesa que todavia no esta cerrada, por lo tanto, se agrega el producto a la lista de pedidos de esa mesa"));
                                
                                $pedidoParaPreparar = new Pedido_detalle();
                                $pedidoParaPreparar->id_pedido_general = $pedido->id;
                                $pedidoParaPreparar->id_producto_pedido = $id_producto_pedido;
                                $pedidoParaPreparar->estado = 'Pendiente';
                                $pedidoParaPreparar->save();
        
                                $mesaAVerificar->estado='Cliente esperando pedido';
                                $mesaAVerificar->save();
                            }
                        }
                        else
                        {
                            $payload = json_encode(array("mensaje" => "No hay stock del producto"));
                        }
                    }
                    else
                    {
                        $payload = json_encode(array("mensaje" => "El producto no existe"));
                    }
                }
                else
                {
                    $payload = json_encode(array("mensaje" => "El que atiende debe ser un mozo"));
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Id de mozo incorrecto"));
            }
        } 
        else 
        {
            $payload = json_encode(array("mensaje" => "Error al crear el pedido, falta ingresar algun dato"));
        } 

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();

        $pedidoId = $args['id'];

        $id_producto_pedido = $ArrayDeParametros['id_producto_pedido'];
        $id_empleado_asignado = $ArrayDeParametros['id_empleado_asignado'];
        $estado = $ArrayDeParametros['estado'];
        $tiempo_de_preparacion = $ArrayDeParametros['tiempo_de_preparacion'];

        $pedidoAModificar = Pedido_detalle:: where('id_pedido_general', '=', $pedidoId)
                                            ->where('id_producto_pedido','=', $id_producto_pedido)->first();

        /*if(isset($id_producto_pedido, $id_empleado_asignado, $tiempo_de_preparacion, $estado) && !empty($id_producto_pedido) && !empty($id_empleado_asignado) && !empty($tiempo_de_preparacion) && !empty($estado))  */
        if(isset($id_producto_pedido, $estado) && !empty($id_producto_pedido) && !empty($estado)) 
        {
            if ($pedidoAModificar != null) 
            {
                switch ($pedidoAModificar->estado) 
                {
                    case 'pendiente':

                        switch ($estado)
                        {
                            case 'en preparacion':
                                if(isset($id_empleado_asignado,$tiempo_de_preparacion) && !empty($id_empleado_asignado) && !empty($tiempo_de_preparacion))
                                {
                                    $pedidoAModificar->id_empleado_asignado = $id_empleado_asignado;
                                    $pedidoAModificar->tiempo_de_preparacion = $tiempo_de_preparacion;
                                    $pedidoAModificar->hora_inicio_preparacion = date('Y-m-d H:i:s');
                                    $pedidoAModificar->estado = $estado;
                                    $payload = json_encode(array("mensaje" => "esta todo ok"));
                                }
                                else
                                {
                                    $payload = json_encode(array("mensaje" => "Para cambiar el estado a 'en prepatacion' se debe ingresar que empleado lo va a realizar y el tiempo estimado de preparacion"));
                                }
                                break;
                            case 'cancelado':
                                $pedidoAModificar->estado = $estado;
                                $payload = json_encode(array("mensaje" => "Cancelado"));
                                break;
                            case 'pendiente':
                                if(isset($id_producto_pedido) && !empty($id_producto_pedido))
                                {
                                    $pedidoAModificar->id_producto_pedido = $id_producto_pedido;
                                    $payload = json_encode(array("mensaje" => "Producto modificado"));
                                }
                                else
                                {
                                    $payload = json_encode(array("mensaje" => "Falta el dato del producto a modificar"));
                                }
                                break;
                            default:
                                $payload = json_encode(array("mensaje" => "El pedido esta prendiente, solo puede tomar los estados en preparacion, cancelado o seguir con el mismo estado"));
                                break;
                        }
                            /* $pedidoAModificar->id_empleado_asignado = $id_empleado_asignado;
                            $pedidoAModificar->id_producto_pedido = $id_producto_pedido;
                            $pedidoAModificar->tiempo_de_preparacion = $tiempo_de_preparacion;
                            $pedidoAModificar->estado = $estado;
                            $pedidoAModificar->hora_inicio_preparacion = date('Y-m-d H:i:s');
                            $payload = json_encode(array("mensaje" => "Modificado con exito")); */
                        
                        break;
                    case 'en preparacion':
                        
                        if ($estado == 'listo para servir')
                        {
                            $pedidoAModificar->estado = $estado;
                            $pedidoAModificar->hora_fin_preparacion = date('Y-m-d H:i:s');
                            $payload = json_encode(array("mensaje" => "Modificado con exito")); 
                        } 
                        else if($estado == 'cancelado')
                        {
                            $pedidoAModificar->estado = $estado;
                            $payload = json_encode(array("mensaje" => "cancelado con exito"));
                        }
                        else 
                        {
                            $payload = json_encode(array("mensaje" => "El pedido esta en preparacion, solo puede tomar los estados cancelado o listo para servir"));
                        }
                        break;
                    case 'listo para servir':
                        
                        if ($estado == 'servido') 
                        {
                            $pedidoAModificar->estado = $estado;
                            $pedidoGeneral = Pedido_general:: find($pedidoAModificar->id_pedido_general);
                            $mesa = Mesa::where('id','=',$pedidoGeneral->id_mesa)->first();
                            $mesa->estado = 'con cliente comiendo';
                            $mesa->save();
                            $payload = json_encode(array("mensaje" => "Modificado con exito")); 
                        } 
                        else 
                        {
                            $payload = json_encode(array("mensaje" => "El pedido esta listo para servir, solo puede tomar el estado servido"));
                        }
                        break;

                    case 'cancelado':
                        
                        $payload = json_encode(array("mensaje" => "El pedido esta cancelado, no se puede modificar"));
                        break;

                    case 'servido':
                    
                        $payload = json_encode(array("mensaje" => "El pedido esta servido, no se puede modificar"));
                        break;
                }
            } 
            else 
            {
                $payload = json_encode(array("mensaje" => "Pedido no encontrado"));
            }
        }
        else 
        {
            $payload = json_encode(array("mensaje" => "Error al modificar el pedido, falta ingresar algun dato"));
        } 

        $pedidoAModificar->save(); 

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $id = $request->getAttribute('id');

        $pedidoABorrar = new Pedido_general();
        $pedidoABorrar->id = $id;

        $cantidadDeBorrados = $pedidoABorrar->BorrarPedido();

        $objDelaRespuesta = new stdclass();
        $objDelaRespuesta->cantidad = $cantidadDeBorrados;

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function GenerarPDF ($request, $response, $args)
    {
      $pdf = new PDF();
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Times','',12);
    
      $todos = Pedido_detalle::all();
      $total= Pedido_detalle::all()->count();
      //$venta = Venta::find(1);
  
      $pdf->Cell(30,10,'id',1,0,'C',0);
      $pdf->Cell(60,10,'id_mesa',1,0,'C',0);
      $pdf->Cell(30,10,'id_mozo',1,0,'C',0);
      $pdf->Cell(30,10,'nombre_cliente',1,0,'C',0);
      $pdf->Cell(30,10,'hora_ingreso',1,0,'C',0);
      $pdf->Cell(30,10,'hora_egreso',1,0,'C',0);
      $pdf->Cell(30,10,'total',1,0,'C',0);
      $pdf->Cell(30,10,'promedio_puntuacion',1,0,'C',0);
      $pdf->Cell(30,10,'fechaBaja',1,1,'C',0);
      
      if($total >1)
      {
        foreach($todos as $unoSolo)
        {
          $pdf->Cell(30,10,$unoSolo->id,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->id_mesa,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->id_mozo,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->nombre_cliente,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->hora_ingreso,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->hora_egreso,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->total,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->promedio_puntuacion,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->fechaBaja,1,1,'C',0);
        }
      }
      else
      {
        $pdf->Cell(30,10,$todos->id,1,0,'C',0);
        $pdf->Cell(60,10,$todos->id_mesa,1,0,'C',0);
        $pdf->Cell(30,10,$todos->id_mozo,1,0,'C',0);
        $pdf->Cell(30,10,$todos->nombre_cliente,1,0,'C',0);
        $pdf->Cell(60,10,$todos->hora_ingreso,1,0,'C',0);
        $pdf->Cell(30,10,$todos->hora_egreso,1,0,'C',0);
        $pdf->Cell(30,10,$todos->total,1,0,'C',0);
        $pdf->Cell(60,10,$todos->promedio_puntuacion,1,0,'C',0);
        $pdf->Cell(30,10,$todos->fechaBaja,1,1,'C',0);
      }
  
      $pdf->Output();
  
      return $response;
    }

    public function GenerarCSV ($request, $response, $args)
    {
      $archivo = fopen("../archivosGenerados/Pedidos_detalle.csv",'w');
  
      $todos = Pedido_detalle::all();
  
      if($archivo)
      {
        foreach($todos as $unoSolo)
        {
          $datos = $unoSolo ->id .",". $unoSolo ->id_mesa . "," . $unoSolo ->id_mozo . "," . $unoSolo ->nombre_cliente . "," . $unoSolo ->hora_ingreso . "," . $unoSolo ->hora_egreso . "," . $unoSolo ->total . "," . $unoSolo ->promedio_puntuacion . "," . $unoSolo ->fechaBaja . "\n";
          
          fputs($archivo , $datos);
        }
  
        $response->getBody()->write("archivo guardado");
        fclose($archivo); 
      }
      else
      {
        $response->getBody()->write("no se pudo guardar el archivo");
      }
      
      return $response
      ->withHeader('Content-Type', 'application/json');
    }

    public function OperacionesPorSector ($request, $response, $args)
    {
        $mozos = Pedido_general :: selectRaw('count(*) as cantOperaciones')->get() ;//all()->count();
                                            
        $bartender = Pedido_detalle ::  selectRaw('count(*) as cantOperaciones')                                          
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','bartender')->get();
        
        
        $cocinero = Pedido_detalle ::   selectRaw('count(*) as cantOperaciones')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','cocinero')->get();
                                        
        $cervecero = Pedido_detalle ::  selectRaw('count(*) as cantOperaciones')                                        
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','cervecero')->get(); 

        $payload = json_encode(array("Mozos" => $mozos, "Bartenders"=> $bartender, "Cocineros"=>$cocinero, "Cerveceros"=>$cervecero));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function OperacionesPorSectorPorUsuario ($request, $response, $args)
    {
        $mozos = Pedido_general :: select('pedidos_general.id_mozo','usuarios.nombre')
                                        ->selectRaw('count(*) as cantOperaciones') 
                                        ->groupBy('pedidos_general.id_mozo')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_general.id_mozo')->get();
                                            
        $bartender = Pedido_detalle :: select('pedidos_detalle.id_empleado_asignado','usuarios.nombre')
                                        ->selectRaw('count(*) as cantOperaciones') 
                                        ->groupBy('pedidos_detalle.id_empleado_asignado')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','bartender')->get(); 
        
        $cocinero = Pedido_detalle :: select('pedidos_detalle.id_empleado_asignado','usuarios.nombre')
                                        ->selectRaw('count(*) as cantOperaciones') 
                                        ->groupBy('pedidos_detalle.id_empleado_asignado')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','cocinero')->get();
                                        
        $cervecero = Pedido_detalle :: select('pedidos_detalle.id_empleado_asignado','usuarios.nombre')
                                        ->selectRaw('count(*) as cantOperaciones') 
                                        ->groupBy('pedidos_detalle.id_empleado_asignado')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where('usuarios.tipo','=','cervecero')->get(); 

        $payload = json_encode(array("Mozos" => $mozos, "Bartenders"=> $bartender, "Cocineros"=>$cocinero, "Cerveceros"=>$cervecero));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }


    public function OperacionesPorUsuario ($request, $response, $args)
    {
        $id_usuario = $args['id_usuario'];
        $verificarUsuario = Usuario:: find($id_usuario);

        if($verificarUsuario!=null)
        {
            if($verificarUsuario->tipo == 'mozo')
            {
                $usuario = Pedido_general :: select('pedidos_general.id_mozo','usuarios.nombre')
                                            ->selectRaw('count(*) as cantOperaciones') 
                                            ->groupBy('pedidos_general.id_mozo')                                           
                                            ->join('usuarios','usuarios.id', '=','pedidos_general.id_mozo')
                                            ->where('pedidos_general.id_mozo', '=', $id_usuario)->get();
            }
            else
            {
                $usuario = Pedido_detalle :: select('pedidos_detalle.id_empleado_asignado AS id_empleado','usuarios.nombre','usuarios.tipo AS puesto')
                                        ->selectRaw('count(*) as cantOperaciones') 
                                        ->groupBy('pedidos_detalle.id_empleado_asignado')                                           
                                        ->join('usuarios','usuarios.id', '=', 'pedidos_detalle.id_empleado_asignado')
                                        ->where ('pedidos_detalle.id_empleado_asignado','=', $id_usuario)->get(); 
            }
        }
        else
        {
            $usuario = "El usuario no existe";
        }
                                            
        $payload = json_encode(array("Usuario" => $usuario));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ProductoMasVendido($request, $response, $args)
    {
        $productos = Pedido_detalle :: select('Pedidos_detalle.id_producto_pedido','productos.nombre')
                                            ->selectRaw('count(*) as cantVendidos') 
                                            ->groupBy('Pedidos_detalle.id_producto_pedido')
                                            ->join('productos','Pedidos_detalle.id_producto_pedido','=','productos.id')
                                            ->orderBy('cantVendidos','desc')->first();  
                                            
        $payload = json_encode(array("Usuario" => $productos));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ProductoMenosVendido($request, $response, $args)
    {
        $productos = Producto :: select ('Pedidos_detalle.id_producto_pedido','productos.nombre')
                                        ->selectRaw('count(*) as cantVendidos') 
                                        ->groupBy('Pedidos_detalle.id_producto_pedido')
                                        ->join('Pedidos_detalle','Pedidos_detalle.id_producto_pedido','=','productos.id')
                                        ->orderBy('cantVendidos','asc')->first();   

        $payload = json_encode(array("Usuario" => $productos));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function PedidosCancelados($request, $response, $args)
    {
        $pedido = Pedido_detalle :: where('estado','=','cancelado')->get();

        $payload = json_encode(array("Cancelados" => $pedido));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMenosUsada($request, $response, $args)
    {
        $mesa = Pedido_general :: select('Pedidos_general.id_mesa')
                                ->selectRaw('count(*) as cantUso') 
                                ->groupBy('Pedidos_general.id_mesa')
                                ->orderBy('cantUso','ASC')->first();  
                                            
        $payload = json_encode(array("Mesa" => $mesa));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMasUsada($request, $response, $args)
    {
        $mesa = Pedido_general :: select('Pedidos_general.id_mesa')
                                ->selectRaw('count(*) as cantUso') 
                                ->groupBy('Pedidos_general.id_mesa')
                                ->orderBy('cantUso','DESC')->first();  
                                            
        $payload = json_encode(array("Mesa" => $mesa));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaConMayorImporte($request, $response, $args)
    {
        $mesa = Pedido_general :: orderBy('total','DESC')->first();   
                                            
        $payload = json_encode(array("Mesa que menos facturo" => $mesa));

        $response->getBody()->write($payload);
    }

    public function MesaConMenorImporte($request, $response, $args)
    {
        $mesa = Pedido_general :: orderBy('total','ASC')->first();  
        
        $payload = json_encode(array("Mesa que mas facturo" => $mesa));
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaConMejorComentario($request, $response, $args)
    {
        $mesa = Pedido_general :: orderBy('promedio_puntuacion','DESC')->first();   
                                            
        $payload = json_encode(array("Mesa que menos facturo" => $mesa));

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaConPeorComentario($request, $response, $args)
    {
        $mesa = Pedido_general :: orderBy('promedio_puntuacion','ASC')->first();  
        
        $payload = json_encode(array("Mesa que mas facturo" => $mesa));
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }



}
