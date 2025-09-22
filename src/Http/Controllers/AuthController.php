<?php

namespace App\Http\Controllers;

use App\Http\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        $error = $this->getFlash();

        return $this->render('auth/login.twig', [
            'error' => $error && $error['type'] === 'error' ? $error['message'] : null
        ]);
    }

    public function login(): Response
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->auth()->attempt($username, $password)) {
            return $this->redirect('/admin');
        }

        return $this->redirectWithError('/', 'Invalid username or password');
    }

    public function logout(): Response
    {
        $this->auth()->logout();
        return $this->redirect('/');
    }
}