<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers source processor that are tagged with "tree_house.io.source_processor" to the DelegatingSourceProcessor.
 */
class RegisterSourceProcessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tree_house.io.source.processor.delegating');

        $passes = [];
        foreach ($container->findTaggedServiceIds('tree_house.io.source_processor') as $id => $instances) {
            $passes[$id] = reset($instances);
        }

        uasort($passes, function ($a, $b) {
            $a = isset($a['priority']) ? $a['priority'] : 0;
            $b = isset($b['priority']) ? $b['priority'] : 0;

            return $a > $b ? -1 : 1;
        });

        foreach (array_keys($passes) as $id) {
            $definition->addMethodCall('registerProcessor', [new Reference($id)]);
        }
    }
}
