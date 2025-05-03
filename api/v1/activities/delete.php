<?php
use helpers\ApiResponse;
use helpers\AuthMiddleware;
use controllers\ActivitiesController;


$input = json_decode(file_get_contents("php://input"), true);
$currentUser = AuthMiddleware::authenticate();


if ($currentUser) {
    ActivitiesController::delete($input);
} else {
    ApiResponse::error('Acceso denegado', 'Token inválido', 401);
}