<?php

namespace TreeHouse\IoBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class TreeHouseIoExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('source.yml');
        $loader->load('import.yml');
        $loader->load('export.yml');
        $loader->load('scrape.yml');
        $loader->load('commands.yml');

        $this->setParameters($container, $config);
        $this->loadImportLogger($container, $config['import']);
        $this->loadBridges($container, $config['bridges']);

        // set aliases
        $container->setAlias('tree_house.io.origin_manager', $config['origin_manager_id']);
        $container->setAlias('tree_house.io.source_manager', $config['source_manager_id']);
        $container->setAlias('tree_house.io.source_processor', $config['source_processor_id']);
        $container->setAlias('tree_house.io.source_cleaner', $config['source_cleaner_id']);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function setParameters(ContainerBuilder $container, array $config)
    {
        $this->setConfigParameters($container, $config, ['tree_house', 'io']);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     * @param array            $prefixes
     */
    protected function setConfigParameters(ContainerBuilder $container, array $config, array $prefixes = [])
    {
        foreach ($config as $key => $value) {
            $newPrefixes = array_merge($prefixes, [$key]);

            if (is_array($value) && !is_numeric(key($value))) {
                $this->setConfigParameters($container, $value, $newPrefixes);

                continue;
            }

            $name = implode('.', $newPrefixes);
            $container->setParameter($name, $value);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function loadImportLogger(ContainerBuilder $container, array $config)
    {
        if (!isset($config['item_logger'])) {
            return;
        }

        $id = 'tree_house.io.import.item_logger';

        $loggerConfig = $config['item_logger'];
        if (isset($loggerConfig['type'])) {
            $loggerClass = $container->getParameter(sprintf('tree_house.io.import.item_logger.%s.class', $loggerConfig['type']));
            $definition = new Definition($loggerClass);

            if ($loggerConfig['type'] !== 'array') {
                if (!isset($loggerConfig['client'])) {
                    throw new \LogicException(sprintf('You must define a "client" when for item_logger type "%s"', $loggerConfig['type']));
                }

                $definition->addArgument(new Reference($loggerConfig['client']));
            }

            $container->setDefinition($id, $definition);
        } elseif ($loggerConfig['service']) {
            $container->setAlias($id, $loggerConfig['service']);
        } else {
            return;
        }

        $factory = $container->getDefinition('tree_house.io.import.import_factory');
        $factory->addMethodCall('setItemLogger', [new Reference($id)]);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $bridges
     */
    protected function loadBridges(ContainerBuilder $container, array $bridges)
    {
        $bridgeDir = __DIR__ . '/../Bridge';

        foreach ($bridges as $bridge) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator(sprintf('%s/%s/Resources/config', $bridgeDir, $bridge)));
            $loader->load('services.yml');
        }
    }
}
