<?php

namespace App\Controllers;

use Exception;

abstract class BaseController
{
    protected function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function getJsonInput(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendJsonResponse(['success' => false, 'error' => 'JSON mal formado.'], 400);
        }
        return $data ?? [];
    }

    protected function ensureLoggedIn(): int
    {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Usuario no autenticado.'], 401);
        }
        return (int)$_SESSION['user_id'];
    }

    protected function ensureAdmin(): void
    {
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'Admin') {
            $this->sendJsonResponse(['success' => false, 'error' => 'Acceso denegado. Se requiere rol de administrador.'], 403);
        }
    }
}