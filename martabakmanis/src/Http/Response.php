<?php

namespace MyLib\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;

class Response implements ResponseInterface
{
    private ResponseInterface $psrResponse;
    private LoggerInterface $logger;
    private array $successCallback = [];
    private array $errorCallback = [];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [], ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? $GLOBALS['framework_logger'] ?? new \Psr\Log\NullLogger();

        $stream = Utils::streamFor($content);
        $this->psrResponse = new PsrResponse($statusCode, $headers, $stream);

        $this->logger->debug('Response created', [
            'status_code' => $statusCode,
            'headers' => $headers,
            'content_length' => strlen($content)
        ]);
    }

    public function getStatusCode(): int
    {
        return $this->psrResponse->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withStatus($code, $reasonPhrase);
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->psrResponse->getReasonPhrase();
    }

    public function getProtocolVersion(): string
    {
        return $this->psrResponse->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withProtocolVersion($version);
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->psrResponse->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrResponse->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->psrResponse->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->psrResponse->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withHeader($name, $value);
        return $new;
    }

    public function withAddedHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withAddedHeader($name, $value);
        return $new;
    }

    public function withoutHeader(string $name): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withoutHeader($name);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->psrResponse->getBody();
    }

    public function withBody(StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrResponse = $this->psrResponse->withBody($body);
        return $new;
    }

    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $defaultHeaders = ['Content-Type' => 'text/html; charset=UTF-8'];
        $headers = array_merge($defaultHeaders, $headers);

        return new self($content, $statusCode, $headers);
    }

    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $defaultHeaders = ['Content-Type' => 'application/json; charset=UTF-8'];
        $headers = array_merge($defaultHeaders, $headers);

        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        return new self($content, $statusCode, $headers);
    }

    public static function view(string $viewPath, string $encoding = 'UTF-8', array $variables = []): self
    {

        if (!empty($variables)) {
            extract($variables, EXTR_SKIP);
        }

        ob_start();

        try {

            if (!file_exists($viewPath)) {
                throw new \RuntimeException("View file not found: {$viewPath}");
            }

            include $viewPath;

            $content = ob_get_clean();

        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $headers = ['Content-Type' => "text/html; charset={$encoding}"];

        return new self($content, 200, $headers);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function download(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $filename = $filename ?? basename($filePath);
        $content = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($content),
        ];

        return new self($content, 200, $headers);
    }

    public function ifSuccess(array $data): self
    {
        $this->successCallback = $data;
        return $this;
    }

    public function ifError(array $data): self
    {
        $this->errorCallback = $data;
        return $this;
    }

    public function send(): self
    {
        $statusCode = $this->getStatusCode();

        http_response_code($statusCode);

        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }

        $content = (string) $this->getBody();

        if ($this->hasHeader('Content-Type') && 
            str_contains($this->getHeaderLine('Content-Type'), 'application/json')) {

            $decodedContent = json_decode($content, true);

            if ($decodedContent !== null) {
                if ($statusCode >= 200 && $statusCode < 300 && !empty($this->successCallback)) {
                    $decodedContent = array_merge($decodedContent, $this->successCallback);
                } elseif ($statusCode >= 400 && !empty($this->errorCallback)) {
                    $decodedContent = array_merge($decodedContent, $this->errorCallback);
                }

                $content = json_encode($decodedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $this->logger->info('Response sent', [
            'status_code' => $statusCode,
            'content_type' => $this->getHeaderLine('Content-Type'),
            'content_length' => strlen($content)
        ]);

        echo $content;

        return $this;
    }

    public function getContent(): string
    {
        return (string) $this->getBody();
    }

    public function toArray(): array
    {
        $content = $this->getContent();

        if ($this->hasHeader('Content-Type') && 
            str_contains($this->getHeaderLine('Content-Type'), 'application/json')) {
            return json_decode($content, true) ?: [];
        }

        return ['content' => $content];
    }
}