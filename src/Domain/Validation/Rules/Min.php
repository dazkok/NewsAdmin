<?php

namespace App\Domain\Validation\Rules;

class Min implements Rule
{
    public function validate(string $field, $value, array $params = []): bool
    {
        $min = (int)($params[0] ?? 0);
        return mb_strlen(trim($value ?? '')) >= $min;
    }

    public function getMessage(string $field, $value, array $params = []): string
    {
        $min = (int)($params[0] ?? 0);
        return "The {$field} must be at least {$min} characters";
    }
}