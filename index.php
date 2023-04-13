<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Phiasco\Http\Attributes\Route;
use Phiasco\Http\Router;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;

// EXAMPLE

#[Route(path: '/hello')]
class IndexController
{
    #[Route(path: '/')]
    public function helloWorld(Request $req): Response
    {
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write('Hello World!');

        return $response;
    }

    #[Route(path: '/{name}')]
    public function sayName(Request $req, string $name): Response
    {
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write("Hello {$name}!");

        return $response;
    }

    #[Route(path: '/{name}', methods: ['POST'])]
    public function sayNameButWithPost(Request $req, string $name): Response
    {
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write("Hello {$name}! This is a POST request.");

        return $response;
    }
}

$router = new Router(url: 'http://localhost:8000');

$router->addController(IndexController::class);

$router->dispatch();
