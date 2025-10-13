<?php

namespace MyLib\Routing;

class Url
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function path(mixed $path): Url
    {
        return new self($path);
    }

    public function getPath(): mixed
    {
        return $this->path;
    }

    public function matches(string $requestPath)
    {

        $path = rtrim($this->path, '/');
        $requestPath = rtrim($requestPath, '/');

        if ($path === '' && $requestPath === '') {
            return true;
        }

        return $path === $requestPath;
    }
}