<?php

namespace App\Infrastructure\Twig;

use App\Infrastructure\Support\Csrf;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CsrfExtension extends AbstractExtension
{
    private Csrf $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf_token', [$this, 'getCsrfToken']),
            new TwigFunction('csrf_field', [$this, 'getCsrfField'], ['is_safe' => ['html']])
        ];
    }

    public function getCsrfToken(): string
    {
        return $this->csrf->getToken();
    }

    public function getCsrfField(): string
    {
        return '<input type="hidden" name="csrf" value="' . $this->csrf->getToken() . '">';
    }
}