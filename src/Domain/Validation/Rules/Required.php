<?php

namespace App\Domain\Validation\Rules;

class Required implements Rule
{
    public function validate(string $field, $value, array $params = []): bool
    {
        return !empty(trim($value ?? ''));
    }

    public function getMessage(string $field, $value, array $params = []): string
    {
        return "The {$field} field is required";
    }
}