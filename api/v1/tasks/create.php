<?php
use helpers\AuthMiddleware;
use controllers\TaskController;

$currentUser = AuthMiddleware::authenticate();
$input = json_decode(file_get_contents("php://input"), true);

if ($currentUser) {
    TaskController::create($input);
} else {
    \helpers\ApiResponse::error('Acceso denegado', 'Token inválido', 401);
}