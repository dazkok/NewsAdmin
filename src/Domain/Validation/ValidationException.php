<?php

namespace App\Domain\Validation;

class ValidationException extends \DomainException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => $this->getFirstErrorMessage($this->errors),
            'validation_errors' => $this->errors
        ];
    }

    private function getFirstErrorMessage(array $errors): ?string
    {
        foreach ($errors as $fieldErrors) {
            if (is_array($fieldErrors) && !empty($fieldErrors)) {
                return $fieldErrors[0];
            } elseif (!empty($fieldErrors)) {
                return $fieldErrors;
            }
        }
        return null;
    }
}