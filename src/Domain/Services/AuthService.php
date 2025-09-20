<?php

namespace App\Domain\Services;

class AuthService
{
    private string $username;
    private string $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function attempt(string $login, string $password): bool
    {
        if ($login === $this->username && $password === $this->password) {
            $this->loginUser(['username' => $login]);
            return true;
        }
        return false;
    }

    private function loginUser(array $data): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $data;
    }

    public function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
}