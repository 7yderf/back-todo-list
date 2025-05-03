<?php
use helpers\AuthMiddleware;
use controllers\TaskController;

$currentUser = AuthMiddleware::authenticate();

if ($currentUser) {
// Acceder a los parámetros desde el array $handlerParams
    $id = $handlerParams['id'] ?? null;

    if (!$id) {
        \helpers\ApiResponse::error('ID no proporcionado', null, 400);
        exit;
    }

    TaskController::delete($id);
} else {
    \helpers\ApiResponse::error('Acceso denegado', 'Token inválido', 401);
}

//TaskController::delete($id);