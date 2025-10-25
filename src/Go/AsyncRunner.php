<?php

declare(strict_types=1);

namespace App\Go;

use Symfony\Component\DependencyInjection\Container;

class AsyncRunner
{
    public function __construct(private Container $container)
    {
    }

    public function execute(string $service, string $method, array $params): void
    {
        $command = sprintf(
            'php %s/../../bin/boost.php --service=%s --method=%s --params=%s > /dev/null 2>&1 &',
            __DIR__,
            escapeshellarg($service),
            escapeshellarg($method),
            escapeshellarg(implode(',', $params))
        );
        exec($command);
    }

    public function run(string $service, string $method, array $params): void
    {
        $innerServiceId = $service . '.inner';
        $target = $this->container->has($innerServiceId)
            ? $this->container->get($innerServiceId)
            : $this->container->get($service);

        $target->$method(...$params);
    }
}
