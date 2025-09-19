<?php

namespace App\Repositories\Contracts;

use App\Entities\News;

interface NewsRepositoryInterface
{
    public function all(): array;

    public function find(int $id): ?News;

    public function create(array $data): News;

    public function update(int $id, array $data): ?News;

    public function delete(int $id): bool;
}