<?php

namespace App\Http\Controllers;

use App\Domain\Contracts\LoggerInterface;
use App\Http\Response;
use App\Application\Container;

abstract class Controller
{
    protected function render(string $template, array $data = []): Response
    {
        $view = Container::getInstance()->get('view');
        $content = $view->render($template, $data);
        return new Response($content);
    }

    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return new Response('', $statusCode, ['Location' => $url]);
    }

    protected function json($data, int $statusCode = 200): Response
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return new Response($content, $statusCode, ['Content-Type' => 'application/json']);
    }

    protected function auth()
    {
        return Container::getInstance()->get('authService');
    }

    protected function logger(): LoggerInterface
    {
        return Container::getInstance()->get('logger');
    }

    protected function redirectWithError(string $url, string $message): Response
    {
        $this->setFlash('error', $message);
        return $this->redirect($url);
    }

    protected function redirectWithSuccess(string $url, string $message): Response
    {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $message
        ];

        return $this->redirect($url);
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}