# PHIASCO 🎻

Simple PHP framework for building RESTful APIs.

## Installation

```bash
composer require devlulcas/phiasco
```

## Usage

```php
<?php

use \Psr\Http\Message\ServerRequestInterface as Request;

use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

class Controller {
  #[Route('/')]
  public function get(Request $req): Response 
  {
    $res = new Response();

    $res->getBody()->write('Hello World!');

    return $res;
  }
}
```

