<?php

namespace App\Http\Middleware;

use App\Infrastructure\Support\Csrf;

class CsrfMiddleware
{
    private Csrf $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    public function handle(array $params, array $container): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        $token = $_POST['csrf'] ?? null;
        if (!$token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!$token && str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['csrf'])) {
                $token = $data['csrf'];
            }
        }

        if (!$this->csrf->validate($token ?? null)) {
            if (str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                return false;
            }

            $_SESSION['flash'] = $_SESSION['flash'] ?? [];
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
            header('Location: /');
            return false;
        }

        return true;
    }

    public function __invoke(array $params, array $container): bool
    {
        return $this->handle($params, $container);
    }
}
