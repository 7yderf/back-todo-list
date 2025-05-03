<?php
namespace controllers;

use config\Database;
use helpers\ApiResponse;

class ActivitiesController
{
    public static function index()
    {
        $db = new Database();
        $conn = $db->connect();

        $query = 'SELECT * FROM activities ORDER BY created_at DESC';
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $activities = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        ApiResponse::success(
            $activities,
            'Lista de actividades obtenida exitosamente.',
            200,
            'activities'
        );
    }

    public static function create($input)
    {
        if (!isset($input['data']['attributes']['name'], $input['data']['attributes']['color'])) {
            ApiResponse::error('Nombre y color son requeridos', null, 400);
            return;
        }

        $name = trim($input['data']['attributes']['name']);
        $color = trim($input['data']['attributes']['color']);

        // Validar formato del color (opcional)
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            ApiResponse::error('Formato de color inválido', 'Usa formato hexadecimal (#FFFFFF)', 400);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        try {
            $query = 'INSERT INTO activities (name, color) VALUES (:name, :color)';
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'name' => $name,
                'color' => $color
            ]);

            $activityId = $conn->lastInsertId();

            ApiResponse::success(
                [
                    'id' => $activityId,
                    'name' => $name,
                    'color' => $color
                ],
                'Actividad creada exitosamente.',
                201,
                'activities'
            );
        } catch (\PDOException $e) {
            ApiResponse::error('Error al crear actividad', $e->getMessage(), 500);
        }
    }
    public static function update($input)
    {
        if (!isset($input['data']['attributes']['id'], $input['data']['attributes']['name'], $input['data']['attributes']['color'])) {
            ApiResponse::error('ID, nombre y color son requeridos', null, 400);
            return;
        }

        $id = $input['data']['attributes']['id'];
        $name = trim($input['data']['attributes']['name']);
        $color = trim($input['data']['attributes']['color']);

        // Validar formato del color (opcional)
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            ApiResponse::error('Formato de color inválido', 'Usa formato hexadecimal (#FFFFFF)', 400);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        try {
            $query = 'UPDATE activities SET name = :name, color = :color WHERE id = :id';
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'color' => $color
            ]);

            if ($stmt->rowCount() === 0) {
                ApiResponse::error('Actividad no encontrada', null, 404);
                return;
            }

            ApiResponse::success(
                ['id' => $id, 'name' => $name, 'color' => $color],
                'Actividad actualizada exitosamente.',
                200,
                'activities'
            );
        } catch (\PDOException $e) {
            ApiResponse::error('Error al actualizar actividad', $e->getMessage(), 500);
        }
    }



    public static function delete($input)
    {
        if (!isset($input['data']['attributes']['id'])) {
            ApiResponse::error('ID es requerido', null, 400);
            return;
        }

        $id = $input['data']['attributes']['id'];

        $db = new Database();
        $conn = $db->connect();

        try {
            $query = 'DELETE FROM activities WHERE id = :id';
            $stmt = $conn->prepare($query);
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                ApiResponse::error('Actividad no encontrada', null, 404);
                return;
            }

            ApiResponse::success(
                null,
                'Actividad eliminada exitosamente.',
                200
            );
        } catch (\PDOException $e) {
            ApiResponse::error('Error al eliminar actividad', $e->getMessage(), 500);
        }
    }
}