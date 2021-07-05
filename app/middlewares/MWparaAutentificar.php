<?php

require_once "AutentificadorJWT.php";
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class MWparaAutentificar
{
	private $container;
    
	public function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, RequestHandler $handler)
    {
        $objDelaRespuesta= new stdclass();
		$objDelaRespuesta->respuesta="";

		$header = $request->getHeaderLine('Authorization');
		$token = trim(explode("Bearer", $header)[1]);

		try 
		{
			AutentificadorJWT::verificarToken($token);
			$objDelaRespuesta->esValido=true;      
		}
		catch (Exception $e) 
        {      
			//guardar en un log
			$objDelaRespuesta->excepcion=$e->getMessage();
			$objDelaRespuesta->esValido=false;     
		}

        if($this->container != 'cualquiera')
        {
            if($objDelaRespuesta->esValido)
            {
                if($this->container != 'empleados')
                {
                    $payload=AutentificadorJWT::ObtenerData($token);
        
                    // DELETE,PUT y DELETE sirve para todos los logeados y admin
                    if($payload->perfil ==  $this->container)
                    {
                        $response = $handler->handle($request);
                    }		           	
                    else
                    {	
                        $payload = json_encode(array("mensaje" => "No tiene acceso"));
                        $response = new Response();
                        $response->getBody()->write($payload);
                    }       
                }
                else
                {
                    $response = $handler->handle($request);
                }						
            }    
            else
            {
                //   $response->getBody()->write('<p>no tenes habilitado el ingreso</p>');
                $objDelaRespuesta->respuesta="Solo usuarios registrados";
                $objDelaRespuesta->elToken=$token;
                $payload = json_encode($objDelaRespuesta);
                $response = new Response();
                $response->getBody()->write($payload);
            }
        }
        else
        {
            $response = $handler->handle($request);
        }

        return $response;
    } 
 
}