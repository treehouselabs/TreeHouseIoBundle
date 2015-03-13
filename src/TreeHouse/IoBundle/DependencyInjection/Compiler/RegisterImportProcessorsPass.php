<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterImportProcessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tree_house.io.import.registry');

        foreach ($container->findTaggedServiceIds('tree_house.io.import_processor') as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Service "%s" must define the "alias" attribute on "tree_house.io.import_processor" tags.', $id)
                    );
                }

                $definition->addMethodCall('registerProcessor', [new Reference($id), $attributes['alias']]);
            }
        }
    }
}
