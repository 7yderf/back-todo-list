<?php

use helpers\ApiResponse;
use helpers\AuthMiddleware;
use controllers\ActivitiesController;

$currentUser = AuthMiddleware::authenticate();

if ($currentUser) {
    ActivitiesController::index();
} else {
    ApiResponse::error('Acceso denegado', 'Token inválido', 401);
}