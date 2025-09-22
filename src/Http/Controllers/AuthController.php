<?php

namespace App\Http\Controllers;

use App\Domain\Validation\ValidationException;
use App\Domain\Validation\Validator;
use App\Http\Response;

class AuthController extends Controller
{
    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function showLogin(): Response
    {
        $error = $this->getFlash();

        return $this->render('auth/login.twig', [
            'error' => $error && $error['type'] === 'error' ? $error['message'] : null
        ]);
    }

    public function login(): Response
    {
        try {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $this->logger()->info('Login attempt', ['username' => $username]);

            $this->validator->validateOrFail([
                'username' => $username,
                'password' => $password
            ], [
                'username' => 'required|min:1|max:50',
                'password' => 'required|min:1|max:100'
            ]);

            if ($this->auth()->attempt($username, $password)) {
                $this->logger()->info('Login successful', ['username' => $username]);
                return $this->redirect('/admin');
            }

            $this->logger()->warning('Login failed', ['username' => $username]);
            return $this->redirectWithError('/', 'Invalid username or password');
        } catch (ValidationException $e) {
            $this->logger()->warning('Login validation failed', [
                'username' => $_POST['username'] ?? '',
                'errors' => $e->getErrors()
            ]);

            return $this->redirectWithError('/', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger()->error('Login error', [
                'username' => $_POST['username'] ?? '',
                'error' => $e->getMessage()
            ]);

            return $this->redirectWithError('/', 'Login error occurred');
        }
    }

    public function logout(): Response
    {
        $this->auth()->logout();
        return $this->redirect('/');
    }
}