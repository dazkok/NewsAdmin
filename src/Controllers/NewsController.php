<?php

namespace App\Controllers;

use App\Csrf;
use App\Repositories\Contracts\NewsRepositoryInterface;
use App\View;
use JetBrains\PhpStorm\NoReturn;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class NewsController
{
    private NewsRepositoryInterface $news;
    private View $view;

    public function __construct(NewsRepositoryInterface $news, View $view)
    {
        $this->news = $news;
        $this->view = $view;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(): string
    {
        $articles = $this->news->all();
        return $this->view->render('news/index.twig', ['articles' => $articles]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function show(int $id): string
    {
        $article = $this->news->find($id);
        if ($article) {
            http_response_code(404);
            return $this->view->render('errors/404.twig');
        }
        return $this->view->render('news/show.twig', ['article' => $article]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function edit(int $id): string
    {
        $article = $this->news->find($id);
        if (!$article) {
            http_response_code(404);
            return $this->view->render('errors/404.twig');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->news->update($id, [
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? ''
            ]);
            header('Location: /news/{$id}');
            exit;
        }

        return $this->view->render('news/edit.twig', ['article' => $article]);
    }

    #[NoReturn] public function delete(int $id): void
    {
        $this->news->delete($id);
        header("Location: /news");
        exit;
    }
}