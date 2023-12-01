<?php

declare(strict_types=1);

namespace Http;

class Request
{
    /**
     * @param HttpDataArray $requestData
     * @param HttpDataArray $postData
     * @param HttpDataArray $serverData
     * @param array<string, HttpDataArray> $filesData
     **/
    public function __construct(private array $requestData, private array $postData, private array $serverData, private array $filesData)
    {
    }

    public function getMethod(): string
    {
        return $this->serverData['REQUEST_METHOD'];
    }

    public function getUri(): Uri
    {
        return new Uri($this->serverData['REQUEST_URI']);
    }

    public function getBody(): string
    {
        $body = file_get_contents('php://input');

        if ($body === false) {
            return '';
        }

        return $body;
    }

    /**
     * @return array<PostFile>
     **/
    public function getFilesArray(): array
    {
        return array_map(fn ($fileData) => new PostFile($fileData), $this->filesData);
    }

    /**
     * @return HttpDataArray
     **/
    public function getPostArray(): array
    {
        return $this->postData;
    }

    /**
     * @return HttpDataArray
     **/
    public function getQueryParams(): array
    {
        return $this->requestData;
    }
}
