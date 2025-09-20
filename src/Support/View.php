<?php

namespace App\Support;

use App\Support\Twig\CsrfExtension;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class View
{
    private Environment $twig;
    private Csrf $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;

        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $this->twig = new Environment($loader, [
            'debug' => $_ENV['APP_ENV'] === 'dev',
            'cache' => $_ENV['APP_ENV'] === 'prod' ? dirname(__DIR__, 2) . '/var/cache/twig' : false
        ]);

        $this->twig->addExtension(new CsrfExtension($this->csrf));
    }

    public function render(string $template, array $data = []): string
    {
        try {
            return $this->twig->render($template, $data);
        } catch (LoaderError $e) {
            return "Template error: " . $e->getMessage();
        } catch (RuntimeError $e) {
            return "Runtime error: " . $e->getMessage();
        } catch (SyntaxError $e) {
            return "Syntax error: " . $e->getMessage();
        }
    }
}