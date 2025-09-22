<?php

namespace App\Infrastructure\Support;

use App\Infrastructure\Twig\CsrfExtension;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class View
{
    private Environment $twig;
    private Csrf $csrf;
    private array $globalData = [];

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;

        $loader = new FilesystemLoader(dirname(__DIR__, 3) . '/templates');
        $this->twig = new Environment($loader, [
            'debug' => $_ENV['APP_ENV'] === 'dev',
            'cache' => $_ENV['APP_ENV'] === 'prod' ? dirname(__DIR__, 3) . '/var/cache/twig' : false
        ]);

        $this->twig->addExtension(new CsrfExtension($this->csrf));

        $this->globalData = [
            'app_name' => $_ENV['APP_NAME'] ?? 'News Admin',
            'app_env' => $_ENV['APP_ENV'] ?? 'production'
        ];
    }

    public function addGlobal(string $key, $value): void
    {
        $this->globalData[$key] = $value;
    }

    public function getGlobals(): array
    {
        return $this->globalData;
    }

    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->globalData, $data);

        try {
            $data['auth'] = [
                'check' => function () {
                    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'];
                },
                'user' => $_SESSION['username'] ?? null
            ];

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