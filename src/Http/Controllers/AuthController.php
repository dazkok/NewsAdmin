<?php

namespace App\Http\Controllers;

use App\Http\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->render('login.twig');
    }

    public function login(): Response
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->auth()->attempt($username, $password)) {
            return $this->redirect('/admin');
        }

        return $this->render('login.twig', [
            'error' => 'Invalid username or password'
        ]);
    }

//    #[NoReturn] public function logout(): void
//    {
//        $this->authService->logout();
//        header('Location: /');
//        exit();
//    }
}