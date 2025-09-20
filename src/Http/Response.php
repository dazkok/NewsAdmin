<?php

namespace App\Http;

class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public static function makeFromControllerResult($result): self
    {
        if ($result instanceof self) {
            return $result;
        }

        if (is_string($result)) {
            return new self($result);
        }

        if ($result === null) {
            return new self('', 204); // No Content
        }

        throw new \InvalidArgumentException('Invalid controller response type: ' . gettype($result));
    }
}