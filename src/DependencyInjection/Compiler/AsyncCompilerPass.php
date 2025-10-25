<?php
namespace App\DependencyInjection\Compiler;

use App\Go\AsyncRunner;
use App\Go\Attribute\Async as AsyncAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AsyncCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $cacheDir = $container->getParameter('kernel.cache_dir') . '/async_proxies';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!$class || !class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $interfaces = $reflection->getInterfaceNames();
            $interface = $interfaces[0] ?? null;

            $hasAsync = false;
            foreach ($reflection->getMethods() as $method) {
                if (!empty($method->getAttributes(AsyncAttribute::class))) {
                    $hasAsync = true;
                    break;
                }
            }

            if (!$hasAsync) {
                continue;
            }

            $expectedInterface = $class . 'Interface';
            if (!$interface || $interface !== $expectedInterface || !$reflection->implementsInterface($expectedInterface)) {
                throw new \LogicException(sprintf(
                    'The class "%s" has an #[Async] method but does not implement the required interface "%s".',
                    $class,
                    $expectedInterface
                ));
            }

            $methodsCode = '';
            foreach ($reflection->getMethods() as $method) {
                $methodName = $method->getName();
                if (str_starts_with($methodName, '__')) {
                    continue;
                }

                $attrs = $method->getAttributes(AsyncAttribute::class);
                $params = [];
                $args = [];

                foreach ($method->getParameters() as $param) {
                    $paramCode = '';
                    if ($type = $param->getType()) {
                        if ($type instanceof \ReflectionNamedType) {
                            $paramCode .= ($type->allowsNull() ? '?' : '') . $type->getName() . ' ';
                        }
                    }

                    $paramCode .= '$' . $param->getName();

                    if ($param->isOptional()) {
                        try {
                            $defaultValue = $param->getDefaultValue();
                            $paramCode .= ' = ' . var_export($defaultValue, true);
                        } catch (ReflectionException) {
                        }
                    }

                    $params[] = $paramCode;
                    $args[] = '$' . $param->getName();
                }

                $paramList = implode(', ', $params);
                $argList = implode(', ', $args);

                if (!empty($attrs)) {
                    $methodsCode .= <<<PHP
public function {$methodName}({$paramList}): void {
    \$this->asyncRunner->execute(\\get_class(\$this->inner), '{$methodName}', [{$argList}]);
}

PHP;
                } else {
                    $methodsCode .= <<<PHP
public function {$methodName}({$paramList}): void {
    return \$this->inner->{$methodName}({$argList});
}

PHP;
                }
            }

            $nsParts = explode('\\', $class);
            $className = array_pop($nsParts);
            $namespace = implode('\\', $nsParts);
            $proxyClass = $class . '_AsyncProxy';
            $proxyFile = $cacheDir . '/' . str_replace('\\', '_', $proxyClass) . '.php';

            if (!class_exists($proxyClass, false)) {
                $code = <<<PHP
<?php
namespace {$namespace};

class {$className}_AsyncProxy implements \\{$interface} {
    private \\App\\Go\\AsyncRunner \$asyncRunner;
    private \\{$class} \$inner;

    public function __construct(\\App\\Go\\AsyncRunner \$asyncRunner, \\{$class} \$inner) {
        \$this->asyncRunner = \$asyncRunner;
        \$this->inner = \$inner;
    }

    {$methodsCode}}
PHP;

                file_put_contents($proxyFile, $code);
                require_once $proxyFile;
            }

            $innerId = $id . '.inner';
            $container->setDefinition($innerId, $definition);

            $proxyDef = new Definition($proxyClass, [
                new Reference(AsyncRunner::class),
                new Reference($innerId),
            ]);
            $proxyDef->setPublic($definition->isPublic());
            $container->setDefinition($id, $proxyDef);
        }
    }
}
