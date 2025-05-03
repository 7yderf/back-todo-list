<?php

require_once __DIR__ . '/../../vendor/autoload.php'; // Cargar el autoload de Composer

use helpers\Router;

// Inicializar el enrutador
global $router;
$router = new Router();

// Registrar rutas de autenticación
$router->register('POST', '/api/v1/auth/login', 'auth/login.php');
$router->register('POST', '/api/v1/auth/register', 'auth/register.php');
$router->register('POST', '/api/v1/auth/confirm-email', 'auth/confirmEmail.php');
$router->register('POST', '/api/v1/auth/logout', 'auth/logout.php');
$router->register('POST', '/api/v1/auth/forgot-password', 'auth/forgotPassword.php');

// Registrar rutas comunes
$router->register('POST', '/api/v1/common/reset-password', 'common/resetPassword.php');

// Registrar rutas administrativas
$router->register('POST', '/api/v1/admin/disable-user', 'admin/disableUser.php');

// Resolver rutas protegidas
$router->register('GET', '/api/v1/profile/show', 'profile/showProfile.php');

$router->register('GET', '/api/v1/activities/list', 'activities/index.php');
$router->register('POST', '/api/v1/activities/create', 'activities/create.php');
$router->register('PUT', '/api/v1/activities/update', 'activities/update.php');
$router->register('DELETE', '/api/v1/activities/delete', 'activities/delete.php');

// Registrar rutas de tareas (protegidas)
$router->register('GET', '/api/v1/tasks/list', 'tasks/index.php');
$router->register('GET', '/api/v1/tasks/index/{id}', 'tasks/show.php');
$router->register('POST', '/api/v1/tasks/create', 'tasks/create.php');
$router->register('post', '/api/v1/tasks/update', 'tasks/update.php');
$router->register('DELETE', '/api/v1/tasks/delete/{id}', 'tasks/delete.php');


// Resolver la solicitud actual
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$router->resolve($method, $uri);
