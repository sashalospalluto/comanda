<?php
require_once 'Usuario.php';
require_once 'Usuario_historial.php';
require_once '../app/interfaces/IApiUsable.php';
//require_once '../app/middlewares/AutentificadorJWT.php';

use \App\Models\Usuario as Usuario;
use \App\Models\Usuario_historial as Usuario_historial;

class UsuarioApi implements IApiUsable
{

    public function TraerUno($request, $response, $args)
    {
        $usr = $args['id'];

        $usuario = Usuario::where('id', $usr)->first();
        if($usuario!=null)
        {
            $payload = json_encode($usuario);
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
        $lista = Usuario::all();

        $payload = json_encode(array("listaUsuarios" => $lista));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarUno($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();
        
        $nombre = $ArrayDeParametros['nombre'];
        $apellido = $ArrayDeParametros['apellido'];
        $dni = $ArrayDeParametros['dni'];
        $tipo = $ArrayDeParametros['tipo'];
        $clave = $ArrayDeParametros['clave'];
        
        $nuevoUsuario = new Usuario();
        if(isset($nombre, $apellido, $dni, $tipo) && !empty($nombre) && !empty($apellido) && !empty($dni) && !empty($tipo))
        {
            $usuario = Usuario::where('dni', $dni)->first();
            if($usuario==null)
            {
                $nuevoUsuario->nombre = $nombre;
                $nuevoUsuario->apellido = $apellido;
                $nuevoUsuario->dni = $dni;
                $nuevoUsuario->tipo = $tipo;
                $nuevoUsuario->clave = $clave;
                $nuevoUsuario->fecha_de_ingreso = date('Y-m-d');
                $nuevoUsuario->estado='Activo';
        
                if($nuevoUsuario->save())
                {
                    $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
                }
                else
                {
                    $payload = json_encode(array("mensaje" => "Error al crear usuario"));
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Ya existe un usuario con ese dni"));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al crear usuario, falta ingresar algun dato"));
        }
  
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $ArrayDeParametros = $request->getParsedBody();
        
        $usuarioId = $args['id'];
        
        $nombre = $ArrayDeParametros['nombre'];
        $apellido = $ArrayDeParametros['apellido'];
        $dni = $ArrayDeParametros['dni'];
        $tipo = $ArrayDeParametros['tipo'];
        $estado = $ArrayDeParametros['estado'];
                
        $empleadoAModificar = Usuario::where ('id', '=', $usuarioId)->first();

        if(isset($nombre, $apellido, $dni, $tipo,$estado) && !empty($nombre) && !empty($apellido) && !empty($dni) && !empty($tipo) && !empty($estado))
        {
            if($empleadoAModificar!==null)
            {
                $empleadoAModificar->nombre = $nombre;
                $empleadoAModificar->apellido = $apellido;
                $empleadoAModificar->dni = $dni;
                $empleadoAModificar->tipo = $tipo;
                $empleadoAModificar->estado = $estado;
                
                $empleadoAModificar->save();
                $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Usuario no encontrado"));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al modificar usuario, falta ingresar algun dato"));
        }


        $response->getBody()->write($payload);
       
        return $response->withHeader('Content-Type', 'application/json'); 
    }

    public function BorrarUno($request, $response, $args)
    {
        $usuarioId = $args['id'];
        
        $usuario = Usuario::find($usuarioId);
        
        if($usuario!=null)
        {
            $usuario->delete();
    
            $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));
        }
        else
        {
            $payload = json_encode(array("mensaje" => "el id no existe"));
        }

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public  function hacerLogin($request, $response, $args) 
    {
        $parametros = $request->getParsedBody();
        
        $apellido= $parametros['apellido'];
        $clave= $parametros['clave'];

        $miUsuario = Usuario::where('apellido','=' ,$apellido)
                            ->where('clave','=',$clave)->first();                          

        if($miUsuario!=null)
        {
            $datos = array('apellido' => $miUsuario->apellido,'perfil' => $miUsuario->tipo, 'dni' => $miUsuario->dni);
            $token= AutentificadorJWT::CrearToken($datos); 
            $payload = json_encode(array("TOKEN" => $token));

            //agrego los datos al historials
            /* $dato = Usuario_historial :: all();
            var_dump($dato); */
            $historial = new Usuario_historial();
            $historial->id_usuario = $miUsuario->id;
            $historial->fecha_ingreso = date('Y-m-d H:i:s');
            $historial->save(); 
        }
        else
        {
            $payload = json_encode(array("mensaje" => "el usuario no existe"));
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
    
      $todos = Usuario::all();
      $total= Usuario::all()->count();
      //$venta = Venta::find(1);
  
      $pdf->Cell(10,10,'id',1,0,'C',0);
      $pdf->Cell(20,10,'nombre',1,0,'C',0);
      $pdf->Cell(20,10,'apellido',1,0,'C',0);
      $pdf->Cell(30,10,'dni',1,0,'C',0);
      $pdf->Cell(20,10,'tipo',1,0,'C',0);
      $pdf->Cell(20,10,'estado',1,0,'C',0);
      $pdf->Cell(30,10,'fecha_de_ingreso',1,0,'C',0);
      $pdf->Cell(30,10,'clave',1,0,'C',0);
      $pdf->Cell(17,10,'fechaBaja',1,1,'C',0);
      
      if($total >1)
      {
        foreach($todos as $unoSolo)
        {
          $pdf->Cell(10,10,$unoSolo->id,1,0,'C',0);
          $pdf->Cell(20,10,$unoSolo->nombre,1,0,'C',0);
          $pdf->Cell(20,10,$unoSolo->apellido,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->dni,1,0,'C',0);
          $pdf->Cell(20,10,$unoSolo->tipo,1,0,'C',0);
          $pdf->Cell(20,10,$unoSolo->estado,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->fecha_de_ingreso,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->clave,1,0,'C',0);
          $pdf->Cell(17,10,$unoSolo->fechaBaja,1,1,'C',0);
        }
      }
      else
      {
        $pdf->Cell(10,10,$todos->id,1,0,'C',0);
        $pdf->Cell(20,10,$todos->nombre,1,0,'C',0);
        $pdf->Cell(20,10,$todos->apellido,1,0,'C',0);
        $pdf->Cell(30,10,$todos->dni,1,0,'C',0);
        $pdf->Cell(20,10,$todos->tipo,1,0,'C',0);
        $pdf->Cell(20,10,$todos->estado,1,0,'C',0);
        $pdf->Cell(30,10,$todos->fecha_de_ingreso,1,0,'C',0);
        $pdf->Cell(30,10,$todos->clave,1,0,'C',0);
        $pdf->Cell(17,10,$todos->fechaBaja,1,1,'C',0);
      }
  
      $pdf->Output();
  
      return $response;
    }
  
    public function GenerarCSV ($request, $response, $args)
    {
      $archivo = fopen("../archivosGenerados/Usuarios.csv",'w');
  
      $todos = Usuario::all();
  
      if($archivo)
      {
        foreach($todos as $unoSolo)
        {
          $datos = $unoSolo ->id .",". $unoSolo ->nombre . "," . $unoSolo ->apellido . "," . $unoSolo ->dni . "," . $unoSolo ->tipo . "," . $unoSolo ->estado . "," . $unoSolo ->fecha_de_ingreso . "," . $unoSolo ->clave . "," . $unoSolo ->fechaBaja . "\n";
          
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
