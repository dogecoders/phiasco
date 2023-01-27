<?php

#[Attribute]
class Route
{
    private static array $routes = [];
    private string $path;
    private array $methods;

    public function __construct(string $path, array $methods)
    {
        $this->path = $path;
        $this->methods = $methods;

        self::$routes[] = $this;

        foreach ($this->methods as $value) {
            $this->addRoute($value, $this->path);
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRoutes(): array
    {
        return self::$routes;
    }

    private function addRoute(string $method, string $route, array $params = [])
    {
        // VARIÁVEIS DA ROTA
        $params['variables'] = [];

        // PADRÃO DE VALIDAÇÃO DAS VARIÁVEIS DAS ROTAS
        $patternVariable = '/{(.*?)}/';
        if (preg_match_all($patternVariable, $route, $matches)) {
            $route = preg_replace($patternVariable, '(.*?)', $route);
            $params['variables'] = $matches[1];
        }

        $route = rtrim($route, '/');

        // PADRÃO DE VALIDAÇÃO DA URL
        $patternRoute = '/^' . str_replace('/', '\/', $route) . '$/';

        // ADICIONA A ROTA DENTRO DA CLASSE
        $this->routes[$patternRoute][$method] = $params;
    }
}

#[RouteGroup("/user", as: "resource.user")]
class Controller
{
    #[Route(path: "/", methods: ["GET"])]
    public function list()
    {
        echo "List";
    }

    #[Route(path: "/create",  methods: ["POST"])]
    public function create()
    {
        echo "Create";
    }


    #[Route(path: "/update/{id}",  methods: ["PUT", "PATCH"])]
    public function update(int $id)
    {
        echo "Update $id";
    }
}

function dumpAttributeData(mixed $controllerClass)
{
    $reflection = new ReflectionClass($controllerClass);

    $methods = $reflection->getMethods();

    foreach ($methods as $method) {
        $controllerFn = new ReflectionMethod($reflection->getName(), $method->getName());

        $params = $controllerFn->getParameters();

        $attributes = $method->getAttributes();

        foreach ($attributes as $attribute) {
            $routes = $attribute->newInstance()->getRoutes();
            echo '<pre>';
            print_r($attribute->newInstance()->getPath());
            echo '</pre>';
        }
    }
}

dumpAttributeData(Controller::class);
