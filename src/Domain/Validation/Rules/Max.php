<?php

namespace App\Domain\Validation\Rules;

class Max implements Rule
{
    public function validate(string $field, $value, array $params = []): bool
    {
        $max = (int)($params[0] ?? 0);
        return mb_strlen(trim($value ?? '')) <= $max;
    }

    public function getMessage(string $field, $value, array $params = []): string
    {
        $max = (int)($params[0] ?? 0);
        return "The {$field} may not be greater than {$max} characters";
    }
}