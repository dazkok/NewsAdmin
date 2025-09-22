<?php

namespace App\Domain\Validation;

use App\Domain\Validation\Rules\Rule;

class Validator
{
    private array $rules = [];
    private array $customMessages = [];

    public function __construct()
    {
        $this->registerDefaultRules();
    }

    private function registerDefaultRules(): void
    {
        $this->rules = [
            'required' => new Rules\Required(),
            'min' => new Rules\Min(),
            'max' => new Rules\Max(),
            'safe_html' => new Rules\SafeHtml(),
        ];
    }

    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $fieldRules) as $rule) {
                $ruleParts = explode(':', $rule, 2);
                $ruleName = $ruleParts[0];
                $params = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];

                if (isset($this->rules[$ruleName])) {
                    $ruleInstance = $this->rules[$ruleName];

                    if (!$ruleInstance->validate($field, $value, $params)) {
                        $errors[$field][] = $ruleInstance->getMessage($field, $value, $params);
                    }
                }
            }
        }

        return $errors;
    }

    public function validateOrFail(array $data, array $rules): void
    {
        $errors = $this->validate($data, $rules);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function addRule(string $name, Rule $rule): void
    {
        $this->rules[$name] = $rule;
    }
}