<?php

namespace App\Support;

class Config
{
    private array $env;

    public function __construct(array $env = null)
    {
        $this->env = $env ?? $_ENV ?: [];
    }

    public function get(string $key, $default = null)
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $value = $this->env;
            foreach ($parts as $p) {
                if (is_array($value) && array_key_exists($p, $value)) {
                    $value = $value[$p];
                } else {
                    $envKey = strtoupper(str_replace('.', '_', $key));
                    return getenv($envKey) !== false ? getenv($envKey) : $default;
                }
            }
            return $value ?? $default;
        }

        if (array_key_exists($key, $this->env)) {
            return $this->env[$key];
        }

        $val = getenv($key);
        return $val === false ? $default : $val;
    }
}