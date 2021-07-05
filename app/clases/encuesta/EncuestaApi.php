<?php
require_once 'Encuesta.php';

use \App\Models\Encuesta as Encuesta;
use \App\Models\Pedido_general as Pedido_general;
use \App\Models\Pedido_detalle as Pedido_detalle;
use \App\Models\Producto as Producto;
use \App\Models\Mesa as Mesa;

class EncuestaApi 
{
    public function CompletarEncuesta($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();
        
        $id_mesa = $args['id_mesa'];
        $id_pedido = $args['id_pedido'];
        
        $mesa = $ArrayDeParametros['mesa'];
        $mozo = $ArrayDeParametros['mozo'];
        $cocinero = $ArrayDeParametros['cocinero'];
        $restaurante = $ArrayDeParametros['restaurante'];
        $experiencia = $ArrayDeParametros['experiencia'];

        if(isset($mesa,$mozo,$cocinero,$restaurante,$experiencia) && !empty($mesa) && !empty($mozo) && !empty($cocinero) && !empty($restaurante) && !empty($experiencia))
        {
            $pedidoExistente = Pedido_general :: find($id_pedido);
            $encuestaExistente = Encuesta :: where('id_mesa', '=', $id_mesa)
                                            ->where('id_pedidos_general', '=', $id_pedido)->first();

            if($encuestaExistente==null)
            {
                if($pedidoExistente!=null && $pedidoExistente->id_mesa == $id_mesa)
                {
                    $mesa_a_verificar = Mesa :: find($id_mesa);
    
                    if($mesa_a_verificar->estado == 'con cliente pagando')
                    {
                        $nuevo = new Encuesta();
                        $nuevo->id_mesa = $id_mesa;
                        $nuevo->id_pedidos_general = $id_pedido;
                        $nuevo->mesa = $mesa;
                        $nuevo->mozo = $mozo;
                        $nuevo->cocinero = $cocinero;
                        $nuevo->experiencia = $experiencia;
                        $nuevo->restaurante = $restaurante;

                        if($nuevo->save())
                        {
                            $suma = $mesa + $mozo + $cocinero + $restaurante;
                            $promedio = $suma / 4;

                            $pedidoExistente->promedio_puntuacion = $promedio;
                            $pedidoExistente->save();

                            $payload = json_encode(array("mensaje" => "Encuesta guardada con exito"));
                        }
                        else
                        {
                            $payload = json_encode(array("mensaje" => "Error al crear la encuesta"));
                        }  
                        
                    }
                    else
                    {
                        $payload = json_encode(array("mensaje" => "el cliente debe haber terminado de comer y estar pagando para completar la encuesta"));
                    }
                    
    
                    /* if($nuevo->save())
                    {
                        $payload = json_encode(array("mensaje" => "Mesa creada con exito"));
                    }
                    else
                    {
                        $payload = json_encode(array("mensaje" => "Error al crear la mesa"));
                    } */
    
                }else
                {
                    $payload = json_encode(array("mensaje" => "No se encontro la mesa con el numero de pedido"));
                }
            } else
            {
                $payload = json_encode(array("mensaje" => "Ya existe una encuesta registrada para esa mesa y ese numero de pedido"));
            }                                          

                    
          
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al crear la mesa, falta ingresar algun dato"));
        }
  
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');

    }
}