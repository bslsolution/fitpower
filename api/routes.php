<?php
// api/routes.php
// Acá se anota: "cuando pidan ESTA url, ejecutá ESTE controlador@método"

$router->get('/estado', 'EstadoController@ver');

$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/me', 'AuthController@me');

$router->get('/usuarios', 'UsuarioController@listar');
$router->post('/usuarios', 'UsuarioController@crear');
$router->get('/admin/resumen', 'UsuarioController@resumen');

$router->get('/socios', 'SocioController@listar');
$router->put('/socios/:id/entrenador', 'SocioController@asignarEntrenador');
$router->get('/entrenadores', 'SocioController@listarEntrenadores');
$router->get('/entrenador/resumen', 'SocioController@resumenEntrenador');

$router->get('/ejercicios', 'EjercicioController@listar');
$router->post('/ejercicios', 'EjercicioController@crear');
$router->put('/ejercicios/:id', 'EjercicioController@actualizar');
$router->delete('/ejercicios/:id', 'EjercicioController@borrar');

$router->get('/grupos', 'GrupoMuscularController@listar');
$router->post('/grupos', 'GrupoMuscularController@crear');
$router->put('/grupos/:id', 'GrupoMuscularController@actualizar');
$router->delete('/grupos/:id', 'GrupoMuscularController@borrar');

$router->get('/rutinas', 'RutinaController@listar');
$router->get('/mis-rutinas', 'RutinaController@deSocio');
$router->post('/rutinas', 'RutinaController@crear');
$router->get('/rutinas/:id', 'RutinaController@ver');
$router->put('/rutinas/:id', 'RutinaController@actualizar');
$router->delete('/rutinas/:id', 'RutinaController@borrar');
$router->post('/rutinas/:id/asignar', 'RutinaController@asignar');
$router->post('/rutinas/:id/ejercicios', 'RutinaController@agregarEjercicio');
$router->put('/rutinas/:id/ejercicios/orden', 'RutinaController@reordenarEjercicios');

$router->put('/rutina-ejercicios/:id', 'RutinaController@actualizarEjercicioRutina');
$router->delete('/rutina-ejercicios/:id', 'RutinaController@borrarEjercicioRutina');
$router->post('/rutina-ejercicios/:id/series', 'RutinaController@agregarSerie');
$router->put('/rutina-ejercicios/:id/series/orden', 'RutinaController@reordenarSeries');

$router->put('/series/:id', 'RutinaController@actualizarSerie');
$router->delete('/series/:id', 'RutinaController@borrarSerie');

$router->get('/progreso', 'ProgresoController@listar');
$router->post('/progreso/terminar', 'ProgresoController@terminar');
$router->get('/progreso/:id', 'ProgresoController@ver');

$router->get('/fitpoints', 'FitpointsController@ver');
$router->post('/fitpoints/completar-rutina', 'FitpointsController@completarRutina');
$router->post('/fitpoints/canjear', 'FitpointsController@canjear');

$router->get('/notificaciones', 'NotificacionController@listar');
$router->post('/notificaciones/leer', 'NotificacionController@marcarLeidas');
$router->post('/notificaciones/enviar', 'NotificacionController@enviar');

$router->get('/recompensas', 'RecompensaController@listar');
$router->post('/recompensas', 'RecompensaController@crear');
$router->post('/recompensas/:id', 'RecompensaController@actualizar');
$router->delete('/recompensas/:id', 'RecompensaController@borrar');
