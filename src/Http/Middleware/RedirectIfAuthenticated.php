<?php

namespace App\Http\Middleware;

use App\Http\Response;

class RedirectIfAuthenticated
{
    public function handle(array $params, array $container): Response|bool
    {
        $authService = $container['authService'];

        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($currentUri, PHP_URL_PATH) ?? '/';

        if ($authService->check() && $currentPath === '/') {
            return new Response('', 302, ['Location' => '/admin']);
        }

        return true;
    }
}