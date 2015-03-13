<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers source cleaners that are tagged with "tree_house.io.source_cleaner" to the DelegatingSourceCleaner.
 */
class RegisterSourceCleanersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tree_house.io.source.cleaner.delegating');

        foreach (array_keys($container->findTaggedServiceIds('tree_house.io.source_cleaner')) as $id) {
            $definition->addMethodCall('registerCleaner', [new Reference($id)]);
        }
    }
}
