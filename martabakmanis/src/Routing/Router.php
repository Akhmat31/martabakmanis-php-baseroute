<?php

namespace MyLib\Routing;

use MyLib\Http\Request;
use MyLib\Http\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Router implements RequestHandlerInterface
{
    protected array $routes = [];
    protected array $currentRoute = [];
    protected array $middleware = [];
    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? $GLOBALS['framework_logger'] ?? new \Psr\Log\NullLogger();
    }

    public function __invoke(Url $url, callable $handler): self
    {
        $this->currentRoute = [
            'url' => $url,
            'handler' => $handler,
            'methods' => [],
            'middleware' => [],
            'name' => null
        ];

        return $this;
    }

    public function get(): self
    {
        return $this->addMethod('GET');
    }

    public function post(): self
    {
        return $this->addMethod('POST');
    }

    public function put(): self
    {
        return $this->addMethod('PUT');
    }

    public function delete(): self
    {
        return $this->addMethod('DELETE');
    }

    public function patch(): self
    {
        return $this->addMethod('PATCH');
    }

    public function options(): self
    {
        return $this->addMethod('OPTIONS');
    }

    public function head(): self
    {
        return $this->addMethod('HEAD');
    }

    public function any(): self
    {
        $this->currentRoute['methods'] = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    protected function addMethod(string $method): self
    {
        $this->currentRoute['methods'][] = $method;
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    public function middleware(MiddlewareInterface ...$middleware): self
    {
        if (!empty($this->currentRoute)) {
            $this->currentRoute['middleware'] = array_merge(
                $this->currentRoute['middleware'] ?? [],
                $middleware
            );
        } else {
            $this->middleware = array_merge($this->middleware, $middleware);
        }

        return $this;
    }

    public function name(string $name): self
    {
        if (!empty($this->currentRoute)) {
            $this->currentRoute['name'] = $name;
        }

        return $this;
    }

    public function group(callable $callback, array $attributes = []): self
    {
        $previousMiddleware = $this->middleware;

        if (isset($attributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, $attributes['middleware']);
        }

        call_user_func($callback, $this);

        $this->middleware = $previousMiddleware;

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processRequest($request);
    }

    public function run(): void
    {
        try {
            $request = new Request($this->logger);
            $response = $this->processRequest($request);

            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }

        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    protected function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getUri()->getPath();

        $this->logger->info('Processing request', [
            'method' => $requestMethod,
            'path' => $requestPath,
            'routes_count' => count($this->routes)
        ]);

        foreach ($this->routes as $route) {
            $url = $route['url'];
            $handler = $route['handler'];
            $methods = $route['methods'];
            $routeMiddleware = array_merge($this->middleware, $route['middleware'] ?? []);

            if (!in_array($requestMethod, $methods)) {
                continue;
            }

            $matches = $this->matchRoute($url, $requestPath);
            if ($matches === false) {
                continue;
            }

            if (!empty($matches)) {
                foreach ($matches as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }
            }

            $this->logger->info('Route matched', [
                'route_path' => $url->getPath(),
                'parameters' => $matches
            ]);

            $response = $this->processMiddleware($routeMiddleware, $request, function($req) use ($handler) {
                return $this->callHandler($handler, $req);
            });

            return $response;
        }

        $this->logger->warning('No route found', [
            'method' => $requestMethod,
            'path' => $requestPath
        ]);

        return Response::html('<h1>404 - Page Not Found</h1>', 404);
    }

    protected function matchRoute(Url $url, string $requestPath): array|false
    {
        $routePath = $url->getPath();

        if ($url->matches($requestPath)) {
            return [];
        }

        $routePattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';

        if (preg_match($routePattern, $requestPath, $matches)) {

            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    public function processMiddleware(array $middleware, ServerRequestInterface $request, callable $finalHandler): ResponseInterface
    {
        if (empty($middleware)) {
            return $finalHandler($request);
        }

        $current = array_shift($middleware);

        return $current->process($request, new class($middleware, $finalHandler, $this) implements RequestHandlerInterface {
            public function __construct(
                protected array $middleware,
                protected $finalHandler,
                protected Router $router
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->router->processMiddleware($this->middleware, $request, $this->finalHandler);
            }
        });
    }

    protected function callHandler(callable $handler, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $response = call_user_func($handler, $request);

            if ($response instanceof ResponseInterface) {
                return $response;
            }

            if (is_array($response)) {
                return Response::json($response);
            }

            return Response::html((string) $response);

        } catch (\Throwable $e) {
            $this->logger->error('Handler exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function handleException(\Throwable $e): void
    {
        $this->logger->critical('Router exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        http_response_code(500);

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => 'An unexpected error occurred'
        ]);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function generateUrl(string $name, array $parameters = []): ?string
    {
        foreach ($this->routes as $route) {
            if (($route['name'] ?? null) === $name) {
                $path = $route['url']->getPath();

                foreach ($parameters as $key => $value) {
                    $path = str_replace('{' . $key . '}', $value, $path);
                }

                return $path;
            }
        }

        return null;
    }
}