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
        $this->getRoutesFromController($controller);
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
    private function getRoutesFromController(string $controllerClass)
    {
        $controllerReflection = new \ReflectionClass($controllerClass);

        $controllerAttributes = $controllerReflection->getAttributes();

        // The controller must have a Route attribute to be a route group
        if (count($controllerAttributes) === 0) {
            throw new PhiascoUndefinedRouteGroup($controllerClass);
        }

        $controllerAsRouteGroup = $controllerAttributes[0]->newInstance();

        // We will use the controller's path as the root path
        $rootPath = $controllerAsRouteGroup->path;

        // The handler is defined if some of the sub-routes have a path equals to the root route regex
        $this->routes[$rootPath] = [
            'controller' => $controllerClass,
            'handlers' => [],
        ];

        foreach ($controllerReflection->getMethods() as $method) {
            // Controller Route Attribute
            $routeAttributes = $method->getAttributes();

            // No attribute === No route === No handler
            if (count($routeAttributes) === 0) {
                continue;
            }

            $controllerMethodAsRoute = $routeAttributes[0]->newInstance();

            foreach ($controllerMethodAsRoute->methods as $httpMethod) {
                $this->routes[$rootPath]['handlers'][$httpMethod][$controllerMethodAsRoute->path] = [
                    'handler' => $method->getName(),
                    'params' => $controllerMethodAsRoute->params,
                ];
            }
        }
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
            $supportHttpMethod = array_key_exists($httpMethod, $routeGroup['handlers']);

            // If the HTTP method is not supported, we will try the next route group
            if (!$supportHttpMethod) {
                continue;
            }

            $matchesRoot = preg_match($routeGroupPath, $uri, $matches);

            echo "<pre>";
            print_r([
                'uri' => $uri,
                'routeGroupPath' => $routeGroupPath,
                'matchesRoot' => $matchesRoot,
                'matches' => $matches,
            ]);
            echo "</pre>";
            die;


            // If the URI doesn't match the route group path, we will try the next route group
            if (!$matchesRoot) {
                continue;
            }

            // Now we can create the controller
            $controller = new $routeGroup['controller']();

            // And get the sub-routes
            $subRoutes = $routeGroup['handlers'][$httpMethod];

            $params = [];

            $uri = preg_replace($routeGroupPath, '', $uri);

            foreach ($subRoutes as $subRoutePath => $subRoute) {
                if ($subRoutePath === self::ROOT_ROUTE_REGEX) {
                    // ! DEBUG
                    $handlerReturn = $controller->{$subRoute['handler']}($this->request, ...$params);
                    echo "<pre>";
                    print_r($handlerReturn);
                    echo "</pre>";
                    die;
                }

                // If the sub-route path doesn't match the URI, we will try the next sub-route
                if (!preg_match($subRoutePath, $uri, $matches)) {
                    continue;
                }

                echo "<pre>";
                print_r($matches);
                echo "</pre>";
                die;


                // If the sub-route path matches the URI, we will get the params
                foreach ($subRoute['params'] as $position => $name) {
                    $params[$name] = trim($matches[$position], '/');
                }

                // And finally we will call the handler
                $handlerReturn = $controller->{$subRoute['handler']}($this->request, ...$params);

                // ! DEBUG
                echo "<pre>";
                print_r($handlerReturn);
                echo "</pre>";
                die;
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
