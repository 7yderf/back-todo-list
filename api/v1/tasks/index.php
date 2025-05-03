<?php
use helpers\AuthMiddleware;
use controllers\TaskController;

$currentUser = AuthMiddleware::authenticate();
$input = $_GET; // Parámetros de consulta

if ($currentUser) {
    TaskController::index($input);
} else {
    \helpers\ApiResponse::error('Acceso denegado', 'Token inválido', 401);
}