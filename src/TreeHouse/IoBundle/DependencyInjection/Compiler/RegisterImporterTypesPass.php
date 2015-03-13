<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterImporterTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tree_house.io.import.registry');

        foreach ($container->findTaggedServiceIds('tree_house.io.importer_type') as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Service "%s" must define the "alias" attribute on "tree_house.io.importer_type" tags.', $id)
                    );
                }

                $definition->addMethodCall('registerImporterType', [new Reference($id), $attributes['alias']]);
            }
        }
    }
}
