<?php

namespace MyLib\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MyLib\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'max_age' => 3600,
            'credentials' => false
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($request, $response);
    }

    private function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = Response::html('', 204);
        return $this->addCorsHeaders($request, $response);
    }

    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']))
            ->withHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

        if (!empty($this->config['exposed_headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        if ($this->config['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }   
        return in_array($origin, $this->config['allowed_origins']);
    }
}