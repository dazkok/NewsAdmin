<?php

namespace App\Http\Controllers;

use App\Domain\Repositories\Contracts\NewsRepositoryInterface;
use App\Http\Response;

class NewsController extends Controller
{
    private NewsRepositoryInterface $newsRepository;

    public function __construct(NewsRepositoryInterface $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    public function index(): Response
    {
        $news = $this->newsRepository->all();
        return $this->render('news/news.twig', [
            'news' => $news
        ]);
    }

    public function apiList(): Response
    {
        $news = $this->newsRepository->all();
        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    public function apiGet(int $id): Response
    {
        $news = $this->newsRepository->find($id);

        if (!$news) {
            return $this->json([
                'success' => false,
                'error' => 'News not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    public function store(): Response
    {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true);
            $title = $input['title'] ?? '';
            $content = $input['content'] ?? '';
        } else {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
        }

        if (empty($title) || empty($content)) {
            return $this->json([
                'success' => false,
                'error' => 'Title and content are required'
            ], 400);
        }

        try {
            $news = $this->newsRepository->create([
                'title' => $title,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->json([
                'success' => true,
                'message' => 'News created successfully',
                'news' => $news
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error creating news: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(int $id): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';

        if (empty($title) || empty($content)) {
            return $this->json([
                'success' => false,
                'error' => 'Title and content are required'
            ], 400);
        }

        try {
            $success = $this->newsRepository->update($id, [
                'title' => $title,
                'content' => $content,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                return $this->json([
                    'success' => true,
                    'message' => 'News updated successfully'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'News not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error updating news: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(int $id): Response
    {
        try {
            $success = $this->newsRepository->delete($id);

            if ($success) {
                return $this->json([
                    'success' => true,
                    'message' => 'News deleted successfully'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'News not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error deleting news: ' . $e->getMessage()
            ], 500);
        }
    }
}