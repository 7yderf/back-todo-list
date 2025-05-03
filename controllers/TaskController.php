<?php
namespace controllers;

use config\Database;
use helpers\ApiResponse;

class TaskController
{
    public static function index($input)
    {
        $db = new Database();
        $conn = $db->connect();

        // Parámetros de filtrado
        $page = max(1, $input['page'] ?? 1);
        $perPage = min(50, $input['per_page'] ?? 10);
        $status = $input['status'] ?? null;
        $categoryId = $input['category_id'] ?? null;
        $order = in_array(strtolower($input['order'] ?? 'desc'), ['asc', 'desc']) ? strtoupper($input['order']) : 'DESC';

        // Construir consulta base
        $baseQuery = "SELECT t.*, a.name as category_name, a.color as category_color 
              FROM tasks t
              INNER JOIN activities a ON t.category_id = a.id
              WHERE t.deleted = 0";

        $params = [];

        // Aplicar filtros
        if ($status) {
            $baseQuery .= " AND t.status = :status";
            $params['status'] = $status;
        }
        if ($categoryId) {
            $baseQuery .= " AND t.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        // Ordenamiento
        $baseQuery .= " ORDER BY t.created_at $order";

        // Consulta para el total de registros
        $totalQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as total_query";
        $stmtTotal = $conn->prepare($totalQuery);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue(":$key", $value);
        }
        $stmtTotal->execute();
        $total = $stmtTotal->fetchColumn();

        // Cálculos de paginación
        $lastPage = max(ceil($total / $perPage), 1);
        $currentPage = min($page, $lastPage);
        $offset = ($currentPage - 1) * $perPage;

        // Consulta paginada
        $query = $baseQuery . " LIMIT :offset, :limit";
        $stmt = $conn->prepare($query);

        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular from y to
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);
        if (count($tasks) < $perPage) {
            $to = $offset + count($tasks);
        }

        ApiResponse::success([
            'data' => $tasks,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
                'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null
            ]
        ], 'Lista de tareas obtenida exitosamente.', 200, 'tasks');
    }

    public static function show($id)
    {
        $db = new Database();
        $conn = $db->connect();

        $query = "SELECT t.*, a.name as category_name 
                FROM tasks t
                INNER JOIN activities a ON t.category_id = a.id
                WHERE t.id = :id AND t.deleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $id]);

        $task = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($task) {
            ApiResponse::success($task, 'Tarea encontrada.', 200, 'tasks');
        } else {
            ApiResponse::error('Tarea no encontrada', null, 404);
        }
    }

    public static function create($input)
    {
        if (!isset($input['data']['attributes']['title'], $input['data']['attributes']['category_id'])) {
            ApiResponse::error('Título y categoría son requeridos', null, 400);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        // Verificar que existe la categoría
        $categoryCheck = $conn->prepare("SELECT id FROM activities WHERE id = ?");
        $categoryCheck->execute([$input['data']['attributes']['category_id']]);
        if (!$categoryCheck->fetch()) {
            ApiResponse::error('Categoría no válida', null, 400);
            return;
        }

        $query = "INSERT INTO tasks (title, description, category_id, status) 
                VALUES (:title, :description, :category_id, :status)";
        $stmt = $conn->prepare($query);

        $params = [
            'title' => $input['data']['attributes']['title'],
            'description' => $input['data']['attributes']['description'] ?? null,
            'category_id' => $input['data']['attributes']['category_id'],
            'status' => $input['data']['attributes']['status'] ?? 'pendiente'
        ];

        try {
            $stmt->execute($params);
            $taskId = $conn->lastInsertId();

            ApiResponse::success(
                ['id' => $taskId] + $params,
                'Tarea creada exitosamente.',
                201,
                'tasks'
            );
        } catch (\PDOException $e) {
            ApiResponse::error('Error al crear tarea', $e->getMessage(), 500);
        }
    }

    public static function update($input)
    {
        if (!isset($input['data']['attributes']['id'])) {
            ApiResponse::error('ID es requerido', null, 400);
            return;
        }

        $db = new Database();
        $conn = $db->connect();

        // Obtener tarea existente
        $existingTask = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND deleted = 0");
        $existingTask->execute([$input['data']['attributes']['id']]);
        if (!$existingTask->fetch()) {
            ApiResponse::error('Tarea no encontrada', null, 404);
            return;
        }

        // Construir query dinámica
        $updates = [];
        $params = ['id' => $input['data']['attributes']['id']];
        $allowedFields = ['title', 'description', 'category_id', 'status'];

        foreach ($input['data']['attributes'] as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            ApiResponse::error('No se proporcionaron campos para actualizar', null, 400);
            return;
        }

        $query = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($query);

        try {
            $stmt->execute($params);
            ApiResponse::success(
                $params,
                'Tarea actualizada exitosamente.',
                200,
                'tasks'
            );
        } catch (\PDOException $e) {
            ApiResponse::error('Error al actualizar tarea', $e->getMessage(), 500);
        }
    }

    public static function delete($id)
    {
        $db = new Database();
        $conn = $db->connect();

        $query = "UPDATE tasks SET deleted = 1 WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            ApiResponse::error('Tarea no encontrada', null, 404);
            return;
        }

        ApiResponse::success(null, 'Tarea eliminada exitosamente.', 200);
    }
}