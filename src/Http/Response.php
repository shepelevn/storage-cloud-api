<?php

declare(strict_types=1);

namespace Http;

/**
 * @phpstan-type HttpHeader array{name: string, value: string}
 **/
class Response
{
    private string $body = '';
    private int $code = 200;
    /** @var list<HttpHeader> **/
    private array $headers = [];

    public function __construct()
    {
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function withStatus(int $code): Response
    {
        $clone = clone $this;
        $clone->code = $code;

        return $clone;
    }

    public function withHeader(string $headerName, string $headerValue): Response
    {
        $clone = clone $this;
        $clone->headers[] = ['name' => $headerName, 'value' => $headerValue];

        return $clone;
    }

    /**
     * @return list<HttpHeader>
     **/
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getProtocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }
}
