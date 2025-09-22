<?php

namespace App\Domain\Validation\Rules;

interface Rule
{
    public function validate(string $field, $value, array $params = []): bool;

    public function getMessage(string $field, $value, array $params = []): string;
}