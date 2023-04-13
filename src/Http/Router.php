<?php

declare(strict_types=1);

namespace Phiasco\Http;

use Phiasco\Http\Route;
use Phiasco\Http\Exception\PhiascoUndefinedRouteGroup;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Request;

class Router
{
    private const ROOT_ROUTE_REGEX = '/^$/';

    private string $prefix;

    private array $routes = [];

    private array $controllers = [];

    private Request $request;

    public function __construct(
        string $url,
    ) {
        $this->setPrefix($url);
        $this->request = $this->createServerRequestFromGlobals();
    }

    /**
     * Creates a ServerRequest from the global variables
     */
    private function createServerRequestFromGlobals(): Request
    {
        $serverRequestFactory = new ServerRequestFactory();

        return $serverRequestFactory->createServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER
        );
    }

    /**
     * Defines a global prefix for all routes
     */
    private function setPrefix(string $url)
    {
        $parseUrl = parse_url($url);
        $this->prefix = $parseUrl["path"] ?? "";
    }

    /**
     * Adds a controller to the router. The routes will be defined by the controller's methods
     */
    public function addController(string $controller): void
    {
        $this->routes[] = $this->getRoutesFromController($controller);
    }

    /**
     * Dispatches the routes. This method should be called after all controllers have been added
     */
    public function dispatch(): void
    {
        $this->dispatchRoute();
    }

    /**
     * Extracts the routes from the controller and returns them as an array
     */
    private function getRoutesFromController(string $controllerClass): array
    {
        $controllerReflection = new \ReflectionClass($controllerClass);

        $controllerAttributes = $controllerReflection->getAttributes(Route::class);

        // The controller must have a Route attribute to be a route group
        if (count($controllerAttributes) === 0) {
            throw new PhiascoUndefinedRouteGroup($controllerClass);
        }

        $controllerAsRouteGroup = $controllerAttributes[0]->newInstance();

        if (!($controllerAsRouteGroup instanceof Route)) {
            throw new PhiascoUndefinedRouteGroup($controllerClass);
        }

        // We will use the controller's path as the root path
        $rootPath = $controllerAsRouteGroup->path;

        // The handler is defined if some of the sub-routes have a path equals to the root route regex
        $routeGroups[$rootPath] = [
            'controller' => $controllerClass,
            'methods' => $controllerAsRouteGroup->methods,
            'handler' => null,
            'sub-routes' => [],
        ];

        // Define the routes with the controller methods as handlers
        foreach ($controllerReflection->getMethods() as $method) {
            // Controller Route Attribute
            $routeAttributes = $method->getAttributes(Route::class);

            // No attribute === No route === No handler
            if (count($routeAttributes) === 0) {
                continue;
            }

            $controllerMethodAsRoute = $routeAttributes[0]->newInstance();

            // Match the route path with the root route regex to define the root handler
            if ($controllerMethodAsRoute->path === $this::ROOT_ROUTE_REGEX) {
                // Overrides the root handler
                $routeGroups[$rootPath]['handler'] = $method->getName();
                // Overrides the root methods
                $routeGroups[$rootPath]['methods'] = $controllerMethodAsRoute->methods;
                continue;
            }

            // If the route has a path, it's a sub-route of the controller
            $routeGroups[$rootPath]['sub-routes'][$controllerMethodAsRoute->path] = [
                'methods' => $controllerMethodAsRoute->methods,
                'params' => $controllerMethodAsRoute->params,
                // The controller method is the handler
                'handler' => $method->getName(),
            ];
        }

        return $routeGroups;
    }

    private function getUriWithoutPrefix(): string
    {
        $uri = $this->request->getUri()->getPath();
        return str_replace($this->prefix, '', $uri);
    }

    /**
     * Dispatches the route
     */
    public function dispatchRoute(): void
    {
        $uri = $this->getUriWithoutPrefix();
        $httpMethod = $this->request->getMethod();

        foreach ($this->routes as $routeGroupPath => $routeGroup) {
            // Check if the route group supports the HTTP method
            if (!in_array($httpMethod, $routeGroup["methods"])) {
                continue;
            }

            // First, try the root route
            if (preg_match($routeGroupPath, $uri) && isset($routeGroup['handler'])) {
                // Then use the root handler
                $controller = new $routeGroup['controller']();

                $handlerReturn = $controller->{$routeGroup['handler']}($this->request);

                // ! DEBUG
                echo "<pre>";
                print_r($handlerReturn);
                echo "</pre>";
                die;

            }

            // Check if a piece of the URI matches the route group path
            $xUri = explode('/', $uri);
            $headOfTheUri = array_shift($xUri);
            $tailOfTheUri = implode('/', $xUri);

            if (!preg_match($routeGroupPath, $headOfTheUri)) {
                continue;
            }

            // Then we will try to match the sub-routes
            foreach ($routeGroup['sub-routes'] as $subRoutePath => $subRoute) {
                if (!in_array($httpMethod, $subRoute['methods'])) {
                    continue;
                }

                // ! That doesn't work yet
                if (preg_match($subRoutePath, $tailOfTheUri, $matches)) {
                    $controller = new $routeGroup['controller']();

                    $params = [];

                    foreach ($subRoute['params'] as $position => $name) {
                        $params[$name] = trim($matches[$position], '/');
                    }

                    $handlerReturn = $controller->{$subRoute['handler']}($this->request, ...$params);

                    // ! DEBUG
                    echo "<pre>";
                    print_r($handlerReturn);
                    echo "</pre>";
                    die;
                }
            }
        }

        // ! DEBUG
        echo "<pre>";
        echo "No route found";
        echo "<hr>";
        print_r($this->routes);
        echo "</pre>";
    }
}
