<?php

namespace App\Http\Controllers;

use App\Domain\Services\NewsService;
use App\Domain\Validation\ValidationException;
use App\Domain\Validation\Validator;
use App\Http\Response;

class NewsController extends Controller
{
    private NewsService $newsService;
    private Validator $validator;

    public function __construct(NewsService $newsService, Validator $validator)
    {
        $this->newsService = $newsService;
        $this->validator = $validator;
    }

    public function index(): Response
    {
        $news = $this->newsService->getNewsForPage();

        return $this->render('news/news.twig', [
            'news' => $news
        ]);
    }

    public function apiList(): Response
    {
        $news = $this->newsService->getNewsForApi();

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    public function apiGet(int $id): Response
    {
        if ($id <= 0) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid news ID'
            ], 400);
        }

        $news = $this->newsService->getNewsById($id);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    public function store(): Response
    {
        try {
            $inputData = $this->getRequestData();

            $this->validator->validateOrFail($inputData, [
                'title' => 'required|min:1|max:255|safe_html',
                'content' => 'required|min:1|max:10000|safe_html'
            ]);

            $news = $this->newsService->createNews([
                'title' => trim($inputData['title']),
                'content' => trim($inputData['content']),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->json([
                'success' => true,
                'message' => 'News created successfully',
                'news' => $news
            ], 201);
        } catch (ValidationException $e) {
            return $this->json($e->toArray(), 422);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error creating news: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(int $id): Response
    {
        try {
            if ($id <= 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid news ID'
                ], 400);
            }

            $inputData = $this->getRequestData();

            $this->validator->validateOrFail($inputData, [
                'title' => 'required|min:1|max:255|safe_html',
                'content' => 'required|min:1|max:10000|safe_html'
            ]);

            $success = $this->newsService->updateNews($id, [
                'title' => trim($inputData['title']),
                'content' => trim($inputData['content']),
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
        } catch (ValidationException $e) {
            return $this->json($e->toArray(), 422);
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
            if ($id <= 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid news ID'
                ], 400);
            }

            $success = $this->newsService->deleteNews($id);

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