<?php

declare(strict_types=1);

namespace Phiasco\Http\Exception;

class PhiascoUndefinedRouteGroup extends \Exception
{
    public function __construct(
        private string $class,
    ) {
        parent::__construct("Class {$class} has no route group defined. Use #[Route('/path')] on the controller class.");
    }
}
