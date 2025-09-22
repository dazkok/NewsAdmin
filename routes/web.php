<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Application\Router;

return function (Router $router) {
    // Public routes
    $router->get('/', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/logout', [AuthController::class, 'logout']);

    // Protected admin routes
    $router->get('/admin', [NewsController::class, 'index'], ['auth']);
    $router->get('/admin/news', [NewsController::class, 'list'], ['auth']);
    $router->post('/admin/news', [NewsController::class, 'store'], ['auth', 'csrf']);
    $router->get('/admin/news/{id}/edit', [NewsController::class, 'edit'], ['auth']);
    $router->put('/admin/news/{id}', [NewsController::class, 'update'], ['auth', 'csrf']);
    $router->delete('/admin/news/{id}', [NewsController::class, 'delete'], ['auth', 'csrf']);

    $router->get('/api/news', [NewsController::class, 'apiList'], ['auth']);
    $router->get('/api/news/{id}', [NewsController::class, 'apiGet'], ['auth']);
};