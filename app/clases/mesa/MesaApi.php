<?php
require_once 'Mesa.php';
require_once '../app/interfaces/IApiUsable.php';

use \App\Models\Mesa as Mesa;
use \App\Models\Pedido_general as Pedido_general;
use \App\Models\Pedido_detalle as Pedido_detalle;
use \App\Models\Producto as Producto;

class MesaApi implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $idBuscado = $args['id'];

        $mesa = Mesa::where('id', $idBuscado)->first();
        if($mesa!=null)
        {
            $payload = json_encode($mesa);
        }
        else
        {
            $payload = json_encode(array("mensaje" => "id no encontrado"));
        }
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json'); 
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::all();

        $payload = json_encode(array("lista" => $lista));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarUno($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();
        
        $tamanio = $ArrayDeParametros['tamanio'];
        
        $nuevo = new Mesa();

        if(isset($tamanio) && !empty($tamanio))
        {
            $nuevo->tamanio = $tamanio;
            $nuevo->estado = 'cerrada';
                    
            if($nuevo->save())
            {
                $payload = json_encode(array("mensaje" => "Mesa creada con exito"));
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Error al crear la mesa"));
            }
          
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al crear la mesa, falta ingresar algun dato"));
        }
  
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
     	$ArrayDeParametros = $request->getParsedBody();
        $mesaId = $args['id'];

        $estado = $ArrayDeParametros['estado'];
        $tamanio = $ArrayDeParametros['tamanio'];

        $ArrayDeParametros = $request->getParsedBody();
                
        $mesaAModificar = Mesa::where ('id', '=', $mesaId)->first();

        $pedido_general = Pedido_general :: where('id_mesa', '=', $mesaId)->first();
        $pedido_detalle = Pedido_detalle :: where('id_pedido_general','=', $pedido_general->id)->get();

        if(isset($estado, $tamanio) && !empty($estado) && !empty($tamanio))
        {
            if($mesaAModificar!==null)
            {
                switch ($mesaAModificar->estado) 
                {
                    case 'cerrada':
                        
                        if($estado=='con cliente esperando pedido')
                        {
                            $mesaAModificar->estado = $estado;
                            $mesaAModificar->save();
                            $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                        }
                        else
                        {
                            $payload = json_encode(array("mensaje" => "La mesa se encuentra cerrada y sin comensales"));
                        }

                        break;
                    case 'con cliente esperando pedido':
                        
                        switch ($estado) 
                        {
                            case 'con cliente comiendo':
                                
                                foreach ($pedido_detalle as $pedido) 
                                {
                                    if($pedido->estado == 'listo para servir' || $pedido->estado == 'servido')
                                    {
                                        $error=0;
                                    }
                                    else
                                    {
                                        $error=1;
                                        $payload = json_encode(array("mensaje" => "no hay pedidos o todavia no estan listos para servir, corroborar"));
                                        break;
                                    }

                                    if($error==0)
                                    {
                                        $mesaAModificar->estado = $estado;
                                        $mesaAModificar->save();
                                        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                                    }

                                }
                                break;
                            case 'con cliente pagando':

                                $payload = json_encode(array("mensaje" => "El cliente esta esperando el pedido"));
                                break;

                            case 'cerrada':
                                
                                foreach ($pedido_detalle as $pedido) 
                                {
                                    if($pedido->estado == 'cancelado')
                                    {
                                        $error=0;
                                    }
                                    else
                                    {
                                        $error=1;
                                        $payload = json_encode(array("mensaje" => "Para poder cerrar la mesa con los clientes esperando el pedido, primero se deben cancelar los pedidos"));
                                        break;
                                    }

                                    if($error==0)
                                    {
                                        $mesaAModificar->estado = $estado;
                                        $mesaAModificar->save();
                                        $pedido_general->hora_egreso=date('Y-m-d H:i:s');
                                        $pedido_general->total = 0;
                                        $pedido_general->save();
                                        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                                    }
                                }
                                break; 
                            case 'con cliente esperando pedido':
                                $payload = json_encode(array("mensaje" => "Ya tiene asignado ese estado"));
                                break;
                            default:
                                $payload = json_encode(array("mensaje" => "Estado incorrecto"));
                                break;
                        }
                        break;

                    case 'con cliente comiendo':

                        switch ($estado)
                        {
                            case 'con cliente comiendo':
                                $payload = json_encode(array("mensaje" => "Ya tiene asignado ese estado"));
                                break;
                            case 'con cliente esperando pedido':
                                $payload = json_encode(array("mensaje" => "El cliente ya esta comiendo"));
                                break;
                            case 'con cliente pagando':

                                $mesaAModificar->estado = $estado;
                                $mesaAModificar->save();
                                $contador=0;
                                foreach ($pedido_detalle as $pedido) 
                                {
                                    $producto = Producto :: where('id', '=', $pedido->id_producto_pedido)->first();
                                    $contador+= $producto->precio;
                                }
                                
                                $pedido_general->total = $contador;
                                $pedido_general->save();

                                $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                                break; 
                            case 'cerrada':
                                $payload = json_encode(array("mensaje" => "El cliente esta comiendo, debe pagar primero")); 
                                break;   
                            default:
                                $payload = json_encode(array("mensaje" => "Estado incorrectoo"));
                                break;
                        }
                        break;

                    case 'con cliente pagando':
                        if($estado== 'cerrada')
                        {
                            $mesaAModificar->estado = $estado;
                            $mesaAModificar->save();
                            $pedido_general->hora_egreso=date('Y-m-d H:i:s');
                            $pedido_general->save();
                            $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                        }else
                        {
                            $payload = json_encode(array("mensaje" => "El cliente ya esta pagando, solo se puede cerrar"));
                        }
                        break;
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Mesa no encontrada"));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al modificar una mesa, falta ingresar algun dato"));
        }

        $response->getBody()->write($payload);
       
        return $response->withHeader('Content-Type', 'application/json'); 
    }

    public function BorrarUno($request, $response, $args)
    {
        $mesaId = $args['id'];
        
        $mesa = Mesa::find($mesaId);
        
        if($mesa!=null)
        {
            $mesa->delete();
    
            $payload = json_encode(array("mensaje" => "Mesa borrada con exito"));
        }
        else
        {
            $payload = json_encode(array("mensaje" => "el id no existe"));
        }

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function GenerarPDF ($request, $response, $args)
    {
      $pdf = new PDF();
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Times','',12);
    
      $todos = Mesa::all();
      $total= Mesa::all()->count();
      //$venta = Venta::find(1);
  
      $pdf->Cell(30,10,'id',1,0,'C',0);
      $pdf->Cell(60,10,'estado',1,0,'C',0);
      $pdf->Cell(30,10,'tamanio',1,0,'C',0);
      $pdf->Cell(30,10,'fechaBaja',1,1,'C',0);
      
      if($total >1)
      {
        foreach($todos as $unoSolo)
        {
          $pdf->Cell(30,10,$unoSolo->id,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->estado,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->tamanio,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->fechaBaja,1,1,'C',0);
        }
      }
      else
      {
        $pdf->Cell(30,10,$todos->id,1,0,'C',0);
        $pdf->Cell(60,10,$todos->estado,1,0,'C',0);
        $pdf->Cell(30,10,$todos->tamanio,1,0,'C',0);
        $pdf->Cell(30,10,$todos->fechaBaja,1,1,'C',0);
      }
  
      $pdf->Output();
  
      return $response;
    }

    public function GenerarCSV ($request, $response, $args)
    {
      $archivo = fopen("../archivosGenerados/Mesa.csv",'w');
  
      $todos = Mesa::all();
  
      if($archivo)
      {
        foreach($todos as $unoSolo)
        {
          $datos = $unoSolo ->id .",". $unoSolo ->estado . "," . $unoSolo ->tamanio . "," . $unoSolo ->fechaBaja . "\n";
          
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

}