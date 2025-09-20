<?php

namespace App\Domain\Repositories;

use App\Domain\Models\News;
use App\Domain\Repositories\Contracts\NewsRepositoryInterface;
use App\Infrastructure\Database\Database;
use DateTime;
use PDO;

class NewsRepository implements NewsRepositoryInterface
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        $stmt = $this->db->pdo()->query('SELECT * FROM news ORDER BY created_at DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->mapRowToEntity($row), $rows);
    }

    public function find(int $id): ?News
    {
        $stmt = $this->db->pdo()->prepare("SELECT * FROM news WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function create(array $data): News
    {
        $stmt = $this->db->pdo()->prepare('
            INSERT INTO news (title, content, created_at) 
            VALUES (:title, :content, NOW())
        ');
        $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content']
        ]);

        $id = (int)$this->db->pdo()->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data): ?News
    {
        $stmt = $this->db->pdo()->prepare('
            UPDATE news SET title = :title, content = :content WHERE id = :id
        ');
        $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'id' => $id
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->pdo()->prepare("DELETE FROM news WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * @throws \Exception
     */
    private function mapRowToEntity(array $row): News
    {
        return new News(
            (int)$row['id'],
            $row['title'],
            $row['content'],
            new DateTime($row['created_at'])
        );
    }
}