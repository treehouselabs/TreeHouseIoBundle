<?php

namespace TreeHouse\IoBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterEventListenersPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterExportFeedTypesPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterFeedTypesPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterImporterTypesPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterImportHandlersPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterImportProcessorsPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterReaderTypesPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterSourceCleanersPass;
use TreeHouse\IoBundle\DependencyInjection\Compiler\RegisterSourceProcessorsPass;

class TreeHouseIoBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterEventListenersPass());
        $container->addCompilerPass(new RegisterFeedTypesPass());
        $container->addCompilerPass(new RegisterExportFeedTypesPass());
        $container->addCompilerPass(new RegisterReaderTypesPass());
        $container->addCompilerPass(new RegisterImporterTypesPass());
        $container->addCompilerPass(new RegisterImportHandlersPass());
        $container->addCompilerPass(new RegisterImportProcessorsPass());
        $container->addCompilerPass(new RegisterSourceProcessorsPass());
        $container->addCompilerPass(new RegisterSourceCleanersPass());
    }
}
