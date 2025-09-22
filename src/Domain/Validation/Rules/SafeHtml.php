<?php

namespace App\Domain\Validation\Rules;

class SafeHtml implements Rule
{
    public function validate(string $field, $value, array $params = []): bool
    {
        $dangerousPatterns = [
            '/<script/i', '/javascript:/i', '/onload=/i', '/onerror=/i',
            '/<iframe/i', '/<object/i', '/<embed/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value ?? '')) {
                return false;
            }
        }

        return true;
    }

    public function getMessage(string $field, $value, array $params = []): string
    {
        return "The {$field} contains potentially unsafe content";
    }
}