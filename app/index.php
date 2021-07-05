<?php
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

require_once '../app/middlewares/MWparaAutentificar.php';
require_once '../app/middlewares/MWparaCORS.php';

require_once './clases/usuario/UsuarioApi.php';
require_once './clases/producto/ProductoApi.php';
require_once './clases/mesa/MesaApi.php';
require_once './clases/pedido/PedidoApi.php';
require_once './clases/encuesta/EncuestaApi.php';


// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();

// Set base path
$app->setBasePath('/app');

// Add error middleware
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Eloquent
$container=$app->getContainer();

$capsule = new Capsule;

/*  $capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'comanda',
    'username' => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);  */

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'remotemysql.com',
    'database' => '9c3ZQ7CYke',
    'username' => '9c3ZQ7CYke',
    'port' => '3306',
    'password' => 'qgiQjjUy6h',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);  

$capsule->setAsGlobal();
$capsule->bootEloquent();

$app->post('/login', \UsuarioApi::class . ':hacerLogin');

//                        empleados
$app->group('/usuarios', function (RouteCollectorProxy $group) 
{
  $group->get('/', \UsuarioApi::class . ':traerTodos')->add(new MWparaAutentificar('empleados'));
 
  $group->get('/{id}', \UsuarioApi::class . ':traerUno')->add(new MWparaAutentificar('empleados'));

  $group->post('/', \UsuarioApi::class . ':CargarUno') ->add(new MWparaAutentificar('empleados'));;

  $group->delete('/{id}', \UsuarioApi::class . ':BorrarUno')->add(new MWparaAutentificar('empleados'));

  $group->post('/modificar/{id}', \UsuarioApi::class . ':ModificarUno')->add(new MWparaAutentificar('empleados')); 

  $group->get('/generarPDF/', \UsuarioApi::class . ':GenerarPDF');

  $group->get('/generarCSV/', \UsuarioApi::class . ':GenerarCSV');

}) ;


  //                  Productos
$app->group('/productos', function (RouteCollectorProxy $group) 
{

  $group->get('/', \ProductoApi::class . ':traerTodos');
 
  $group->get('/{id}', \ProductoApi::class . ':traerUno');

  $group->post('/', \ProductoApi::class . ':CargarUno');

  $group->delete('/{id}', \ProductoApi::class . ':BorrarUno');

  $group->post('/modificar/{id}', \ProductoApi::class . ':ModificarUno');

  $group->get('/generarPDF/', \ProductoApi::class . ':GenerarPDF');

  $group->get('/generarCSV/', \ProductoApi::class . ':GenerarCSV');
     
});//->add(new MWparaAutentificar('empleados'));

//                  Mesas
$app->group('/mesas', function (RouteCollectorProxy $group) 
{
  $group->get('/', \MesaApi::class . ':traerTodos');
 
  $group->get('/{id}', \MesaApi::class . ':traerUno');

  $group->post('/', \MesaApi::class . ':CargarUno');

  $group->delete('/{id}', \MesaApi::class . ':BorrarUno');

  $group->post('/modificar/{id}', \MesaApi::class . ':ModificarUno');

  $group->get('/generarPDF/', \MesaApi::class . ':GenerarPDF');

  $group->get('/generarCSV/', \MesaApi::class . ':GenerarCSV');

  
})->add(new MWparaAutentificar('empleados')); 

$app->group('/pedidos', function (RouteCollectorProxy $group)  
{
  $group->get('/', \PedidoApi::class . ':traerTodos')->add(new MWparaAutentificar('empleados'));
 
  $group->get('/{id_mesa}/{id_pedido}', \PedidoApi::class . ':traerUno'); //->add(new MWparaAutentificar('cualquiera'));

  $group->post('/', \PedidoApi::class . ':CargarUno')->add(new MWparaAutentificar('empleados'));

  $group->delete('/{id}', \PedidoApi::class . ':BorrarUno')->add(new MWparaAutentificar('empleados'));

  $group->post('/modificar/{id}', \PedidoApi::class . ':ModificarUno')->add(new MWparaAutentificar('empleados'));

  $group->get('/generarPDF/', \MesaApi::class . ':GenerarPDF');

  $group->get('/generarCSV/', \MesaApi::class . ':GenerarCSV');
});

$app->post('/encuestas/{id_mesa}/{id_pedido}', \EncuestaApi::class . ':CompletarEncuesta');


$app->group('/estadisticas', function (RouteCollectorProxy $group)  
{
  $group->get('/historial', \UsuarioApi::class . ':MostrarHistorial');
  $group->get('/operacionesPorSector', \PedidoApi::class . ':OperacionesPorSector');
  $group->get('/operacionesPorSectorPorUsuario', \PedidoApi::class . ':OperacionesPorSectorPorUsuario');
  $group->get('/operacionesPorUsuario/{id_usuario}', \PedidoApi::class . ':OperacionesPorUsuario');
  $group->get('/masVendido', \PedidoApi::class . ':ProductoMasVendido');
  $group->get('/menosVendido', \PedidoApi::class . ':ProductoMenosVendido');
  $group->get('/pedidosCancelados', \PedidoApi::class . ':PedidosCancelados');
  $group->get('/mesaMasUsada', \PedidoApi::class . ':MesaMasUsada');
  $group->get('/mesaMenosUsada', \PedidoApi::class . ':MesaMenosUsada');
  $group->get('/mesaConMenorImporte', \PedidoApi::class . ':MesaConMenorImporte');
  $group->get('/mesaConMayorImporte', \PedidoApi::class . ':MesaConMayorImporte');
  $group->get('/mesaConMejorComentario', \PedidoApi::class . ':MesaConMejorComentario');
  $group->get('/mesaConPeorComentario', \PedidoApi::class . ':MesaConPeorComentario');


});

$app->run();
