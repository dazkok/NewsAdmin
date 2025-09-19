<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\View;
use JetBrains\PhpStorm\NoReturn;

class AuthController
{
    private AuthService $authService;
    private View $view;

    public function __construct(AuthService $auth, View $view)
    {
        $this->authService = $auth;
        $this->view = $view;
    }

    public function showLogin(): string
    {
        return $this->view->render('login.twig');
    }

    private function login(): string
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->authService->attempt($username, $password)) {
            header('Location: /admin');
            exit;
        }

        return $this->view->render('login.twig', [
            'error' => 'Invalid username or password'
        ]);
    }

    #[NoReturn] public function logout(): void
    {
        $this->authService->logout();
        header('Location: /');
        exit();
    }
}