# PHIASCO 🎻

Simple PHP framework for building RESTful APIs.

## Installation

```bash
composer require dogecoders/phiasco
```

## Usage

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use \Phiasco\Http\Attributes\Route;
use \Phiasco\Http\Router;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Psr7\Factory\ResponseFactory;

#[Route(path: '/app')]
class IndexController
{
    #[Route(path: '/')]
    public function index(Request $req): Response
    {
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write('Hello World!');

        return $response;
    }
}

$router = new Router(url: 'http://localhost:8000');

$router->addController(IndexController::class);

$router->dispatch();
```

OBS: HEAVY WIP
