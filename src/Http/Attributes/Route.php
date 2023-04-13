<?php

declare(strict_types=1);

namespace Phiasco\Http\Attributes;

#[\Attribute]
class Route
{
    public array $methods = ['GET'];
    public array $params = [];

    public function __construct(
        public string $path,
        array $methods = ['GET'],
    ) {
        $this->methods = array_map('strtoupper', $methods);
        $this->parsePath();
    }

    /**
     * Parse the path and replace parameters with regex
     */
    private function parsePath(): void
    {
        $this->path = rtrim($this->path, '/');
        $this->path = str_replace('/', '\/', $this->path);

        $paramPattern = '/{([a-zA-Z0-9]+)}/';

        $this->path = preg_replace_callback($paramPattern, function ($matches) {
            // Push paramenter name to params array '{id}' => ['id']
            $this->params[] = $matches[1];

            // Replace parameter with regex
            return '([a-zA-Z0-9]+)';
        }, $this->path);

        $this->path = '/^' . $this->path . '$/';
    }
}
