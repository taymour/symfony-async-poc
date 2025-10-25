<?php

namespace App;

use App\DependencyInjection\Compiler\AsyncCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AsyncCompilerPass());
    }
}
