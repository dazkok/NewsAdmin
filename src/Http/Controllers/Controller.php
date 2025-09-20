<?php

namespace App\Http\Controllers;

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

//    protected function requireAuth(): void
//    {
//        if (!$this->auth->check()) {
//            $this->redirect('/')->send();
//            exit;
//        }
//    }
}