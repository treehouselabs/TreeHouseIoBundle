<?php

namespace TreeHouse\IoBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterEventListenersPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tree_house.io.event_dispatcher')) {
            return;
        }

        $definition = $container->getDefinition('tree_house.io.event_dispatcher');

        foreach ($container->findTaggedServiceIds('tree_house.io.event_listener') as $id => $events) {
            foreach ($events as $event) {
                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Service "%s" must define the "event" attribute on "tree_house.io.event_listener" tags.',
                            $id
                        )
                    );
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.str_replace(' ', '', ucwords(strtr(preg_replace('/^tree_house.io\./', '', $event['event']), '_-.', '   ')));
                }

                $definition->addMethodCall('addListener', [$event['event'], [new Reference($id), $event['method']]]);
            }
        }
    }
}
