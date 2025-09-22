<?php

namespace App\Infrastructure\Support;

use Random\RandomException;

class Csrf
{
    /**
     * @throws RandomException
     */
    public function generate(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    /**
     * @throws RandomException
     */
    public function getToken(): string
    {
        return $this->generate();
    }

    public function validate(?string $token): bool
    {
        return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$token);
    }
}