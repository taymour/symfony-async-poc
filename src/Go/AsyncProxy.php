<?php

declare(strict_types=1);

namespace App\Go;

use App\Go\Attribute\Async;
use ReflectionClass;

class AsyncProxy
{
    public function __construct(
        private object $service,
        private AsyncRunner $asyncRunner,
    ) {}

    public function __call(string $method, array $arguments)
    {
        $reflection = new ReflectionClass($this->service);

        if (!$reflection->hasMethod($method)) {
            throw new \BadMethodCallException("Method {$method} not found on " . $reflection->getName());
        }

        $methodRef = $reflection->getMethod($method);
        $attributes = $methodRef->getAttributes(Async::class);

        if (!empty($attributes)) {
            $this->asyncRunner->execute(
                service: get_class($this->service),
                method: $method,
                params: $arguments,
            );

            return null;
        }

        return $this->service->$method(...$arguments);
    }
}
