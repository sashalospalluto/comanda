<?php
require_once 'Producto.php';
require_once '../app/interfaces/IApiUsable.php';

use \App\Models\Producto as Producto;

class ProductoApi implements IApiUsable
{
    public function TraerTodos($request, $response, $args)
    {
        $lista = Producto::all();

        $payload = json_encode(array("lista" => $lista));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

   public function TraerUno($request, $response, $args)
    {
        $idBuscado = $args['id'];

        $producto = Producto::where('id', $idBuscado)->first();
        if($producto!=null)
        {
            $payload = json_encode($producto);
        }
        else
        {
            $payload = json_encode(array("mensaje" => "id no encontrado"));
        }
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json'); 
    }

    public function CargarUno($request, $response, $args)
    {
        //$response->getBody()->write("<h1>Cargar uno nuevo</h1>");
        $ArrayDeParametros = $request->getParsedBody();
        
        $nombre = $ArrayDeParametros['nombre'];
        $tipo = $ArrayDeParametros['tipo'];
        $stock = $ArrayDeParametros['stock'];
        $precio = $ArrayDeParametros['precio'];
        
        $nuevoProducto = new Producto();

        if(isset($nombre, $tipo, $stock, $precio) && !empty($nombre) && !empty($tipo) && !empty($stock) && !empty($precio))
        {
            $producto = Producto::where ('nombre', $nombre)
                               ->where ('tipo', $tipo) 
                               ->where ('precio',$precio)->first();
            if($producto==null)
            {
                $nuevoProducto->nombre = $nombre;
                $nuevoProducto->tipo = $tipo;
                $nuevoProducto->stock = $stock;
                $nuevoProducto->precio = $precio;
        
                if($nuevoProducto->save())
                {
                    $payload = json_encode(array("mensaje" => "Producto creado con exito"));
                }
                else
                {
                    $payload = json_encode(array("mensaje" => "Error al crear el producto, no se pudo guardar"));
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Ya existe un producto con ese nombre, tipo y precio"));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al crear el producto, falta ingresar algun dato"));
        }
  
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');

    }

    public function ModificarUno($request, $response, $args)
    {
     	$ArrayDeParametros = $request->getParsedBody();
        $productoId = $args['id'];

        $nombre = $ArrayDeParametros['nombre'];
        $tipo = $ArrayDeParametros['tipo'];
        $stock = $ArrayDeParametros['stock'];
        $precio = $ArrayDeParametros['precio'];
              
        $ProductoAModificar = Producto::where ('id', '=', $productoId)->first();

        if(isset($nombre, $tipo, $stock, $precio) && !empty($nombre) && !empty($tipo) && !empty($stock) && !empty($precio))
        {
            if($ProductoAModificar!==null)
            {
                $ProductoAModificar->nombre = $nombre;
                $ProductoAModificar->tipo = $tipo;
                $ProductoAModificar->stock = $stock;
                $ProductoAModificar->precio = $precio;
                
                $ProductoAModificar->save();
                $payload = json_encode(array("mensaje" => "Producto modificado con exito"));
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Producto no encontrado"));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Error al modificar Producto, falta ingresar algun dato"));
        }

        $response->getBody()->write($payload);
       
        return $response->withHeader('Content-Type', 'application/json'); 
    }

    public function BorrarUno($request, $response, $args)
    {
        $productoId = $args['id'];
        
        $producto = Producto::find($productoId);
        
        if($producto!=null)
        {
            $producto->delete();
    
            $payload = json_encode(array("mensaje" => "Producto borrado con exito"));
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
    
      $todos = Producto::all();
      $total= Producto::all()->count();
      //$venta = Venta::find(1);
  
      $pdf->Cell(30,10,'id',1,0,'C',0);
      $pdf->Cell(60,10,'nombre',1,0,'C',0);
      $pdf->Cell(30,10,'tipo',1,0,'C',0);
      $pdf->Cell(30,10,'stock',1,0,'C',0);
      $pdf->Cell(30,10,'precio',1,0,'C',0);
      $pdf->Cell(30,10,'fechaBaja',1,1,'C',0);
      
      if($total >1)
      {
        foreach($todos as $unoSolo)
        {
          $pdf->Cell(30,10,$unoSolo->id,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->nombre,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->tipo,1,0,'C',0);
          $pdf->Cell(60,10,$unoSolo->stock,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->precio,1,0,'C',0);
          $pdf->Cell(30,10,$unoSolo->fechaBaja,1,1,'C',0);
        }
      }
      else
      {
        $pdf->Cell(30,10,$todos->id,1,0,'C',0);
        $pdf->Cell(60,10,$todos->nombre,1,0,'C',0);
        $pdf->Cell(30,10,$todos->tipo,1,0,'C',0);
        $pdf->Cell(60,10,$todos->stock,1,0,'C',0);
        $pdf->Cell(30,10,$todos->precio,1,0,'C',0);
        $pdf->Cell(30,10,$todos->fechaBaja,1,1,'C',0);
      }
  
      $pdf->Output();
  
      return $response;
    }

    public function GenerarCSV ($request, $response, $args)
    {
      $archivo = fopen("../archivosGenerados/Mesa.csv",'w');
  
      $todos = Producto::all();
  
      if($archivo)
      {
        foreach($todos as $unoSolo)
        {
          $datos = $unoSolo ->id .",". $unoSolo ->nombre . "," . $unoSolo ->tipo . "," . $unoSolo ->stock . "," . $unoSolo ->precio .  "," . $unoSolo ->fechaBaja . "\n";
          
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
