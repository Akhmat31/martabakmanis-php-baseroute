<?php

namespace MyLib\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class Request implements ServerRequestInterface
{
    private ServerRequestInterface $psrRequest;
    private LoggerInterface $logger;
    private array $formData;
    private array $validationRules = [];
    
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? $GLOBALS['framework_logger'] ?? new \Psr\Log\NullLogger();
        $this->psrRequest = ServerRequest::fromGlobals();
        $this->formData = $_POST;
        
        $this->logger->info('New HTTP Request', [
            'method' => $this->getMethod(),
            'uri' => (string) $this->getUri(),
            'user_agent' => $this->getHeaderLine('User-Agent')
        ]);
    }
    public function getServerParams(): array
    {
        return $this->psrRequest->getServerParams();
    }
    
    public function getCookieParams(): array
    {
        return $this->psrRequest->getCookieParams();
    }
    
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withCookieParams($cookies);
        return $new;
    }
    
    public function getQueryParams(): array
    {
        return $this->psrRequest->getQueryParams();
    }
    
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withQueryParams($query);
        return $new;
    }
    
    public function getUploadedFiles(): array
    {
        return $this->psrRequest->getUploadedFiles();
    }
    
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withUploadedFiles($uploadedFiles);
        return $new;
    }
    
    public function getParsedBody()
    {
        return $this->psrRequest->getParsedBody();
    }
    
    public function withParsedBody($data): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withParsedBody($data);
        return $new;
    }
    
    public function getAttributes(): array
    {
        return $this->psrRequest->getAttributes();
    }
    
    public function getAttribute(string $name, $default = null)
    {
        return $this->psrRequest->getAttribute($name, $default);
    }
    
    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withAttribute($name, $value);
        return $new;
    }
    
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withoutAttribute($name);
        return $new;
    }
    
    public function getProtocolVersion(): string
    {
        return $this->psrRequest->getProtocolVersion();
    }
    
    public function withProtocolVersion(string $version): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withProtocolVersion($version);
        return $new;
    }
    
    public function getHeaders(): array
    {
        return $this->psrRequest->getHeaders();
    }
    
    public function hasHeader(string $name): bool
    {
        return $this->psrRequest->hasHeader($name);
    }
    
    public function getHeader(string $name): array
    {
        return $this->psrRequest->getHeader($name);
    }
    
    public function getHeaderLine(string $name): string
    {
        return $this->psrRequest->getHeaderLine($name);
    }
    
    public function withHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withHeader($name, $value);
        return $new;
    }
    
    public function withAddedHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withAddedHeader($name, $value);
        return $new;
    }
    
    public function withoutHeader(string $name): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withoutHeader($name);
        return $new;
    }
    
    public function getBody(): StreamInterface
    {
        return $this->psrRequest->getBody();
    }
    
    public function withBody(StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withBody($body);
        return $new;
    }
    
    public function getRequestTarget(): string
    {
        return $this->psrRequest->getRequestTarget();
    }
    
    public function withRequestTarget(string $requestTarget): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withRequestTarget($requestTarget);
        return $new;
    }
    
    public function getMethod(): string
    {
        return $this->psrRequest->getMethod();
    }
    
    public function withMethod(string $method): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withMethod($method);
        return $new;
    }
    
    public function getUri(): UriInterface
    {
        return $this->psrRequest->getUri();
    }
    
    public function withUri(UriInterface $uri, bool $preserveHost = false): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->psrRequest = $this->psrRequest->withUri($uri, $preserveHost);
        return $new;
    }
    
    public function getBodyAsArray(): array
    {
        $body = (string) $this->getBody();
        
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        parse_str($body, $formData);
        return $formData;
    }
    
    public function form(array $rules = []): array
    {
        $this->validationRules = $rules;
        $validatedData = [];
        $errors = [];
        
        $data = array_merge($this->formData, $this->getBodyAsArray());
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            $this->logger->debug("Validating field: {$field}", [
                'value' => $value,
                'rules' => $fieldRules
            ]);
            
            if (isset($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
                $errors[$field][] = "Field {$field} is required";
                continue;
            }
            
            if ($value !== null && $value !== '') {
                if (isset($fieldRules['length_max']) && strlen($value) > $fieldRules['length_max']) {
                    $errors[$field][] = "Field {$field} exceeds maximum length of {$fieldRules['length_max']}";
                }
                
                if (isset($fieldRules['length_min']) && strlen($value) < $fieldRules['length_min']) {
                    $errors[$field][] = "Field {$field} is below minimum length of {$fieldRules['length_min']}";
                }
                
                if (isset($fieldRules['email']) && $fieldRules['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Field {$field} must be a valid email";
                }
                
                if (isset($fieldRules['numeric']) && $fieldRules['numeric'] && !is_numeric($value)) {
                    $errors[$field][] = "Field {$field} must be numeric";
                }
                
                if (isset($fieldRules['pattern']) && !preg_match($fieldRules['pattern'], $value)) {
                    $errors[$field][] = "Field {$field} does not match required pattern";
                }
            }
            
            $validatedData[$field] = $value;
        }
        
        $result = [
            'data' => $validatedData,
            'errors' => $errors,
            'isValid' => empty($errors)
        ];
        
        if (!empty($errors)) {
            $this->logger->warning('Form validation failed', [
                'errors' => $errors,
                'data' => $validatedData
            ]);
        }
        
        return $result;
    }
    
    public function isJson(): bool
    {
        return str_contains($this->getHeaderLine('Content-Type'), 'application/json') ?? false;
    }
    
    public function isAjax(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function getClientIp(): string
    {
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'HTTP_CLIENT_IP'];
        
        foreach ($headers as $header) {
            $ip = $this->getHeaderLine($header);
            if (!empty($ip)) {
                return explode(',', $ip)[0];
            }
        }
        
        return $this->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
    }
}