<?php

namespace MyLib\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        
        $this->logger->info('Request started', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'ip' => $this->getClientIp($request)
        ]);
        
        $response = $handler->handle($request);
        
        $duration = microtime(true) - $startTime;
        
        $this->logger->info('Request completed', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => $this->formatBytes(memory_get_peak_usage(true))
        ]);
        
        return $response;
    }
    
    private function getClientIp(ServerRequestInterface $request): string
    {
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'HTTP_CLIENT_IP'];
        
        foreach ($headers as $header) {
            $ip = $request->getHeaderLine($header);
            if (!empty($ip)) {
                return explode(',', $ip)[0];
            }
        }
        
        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}