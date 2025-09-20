<?php

namespace App\Domain\Models;

use DateTime;

class News
{
    public int $id;
    public string $title;
    public string $content;
    public DateTime $createdAt;

    public function __construct(int $id, string $title, string $content, DateTime $createdAt)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->createdAt = $createdAt;
    }
}