<?php

namespace DVC\AsyncPagination\DependencyInjection;

use DVC\AsyncPagination\Controller\AjaxEndpointController;
use DVC\AsyncPagination\Controller\ContentElement\AsyncPaginationWrapperController;
use DVC\AsyncPagination\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Path;

class AsyncPaginationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(Path::canonicalize(__DIR__.'/../../config')));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $processedConfiguration = $this->processConfiguration($configuration, $configs);

        $container->getDefinition(AjaxEndpointController::class)
            ->addMethodCall('setSharedMaxAge', [$processedConfiguration['shared_max_age'] ?? null])
        ;

        $container->getDefinition(AsyncPaginationWrapperController::class)
            ->addMethodCall('addTargetFrontendModelTypes', [$processedConfiguration['target_frontend_model_types'] ?? []])
        ;
    }
}
