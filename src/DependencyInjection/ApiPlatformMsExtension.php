<?php

namespace Mtarld\ApiPlatformMsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

// Help opcache.preload discover always-needed symbols
class_exists(FileLocator::class);
class_exists(PhpFileLoader::class);

/**
 * @final @internal
 */
class ApiPlatformMsExtension extends Extension
{
    public function getAlias(): string
    {
        return 'api_platform_ms';
    }

    /**
     * @psalm-param array<array-key, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $loader->load('hydra.php');
        $loader->load('jsonapi.php');
        $loader->load('hal.php');

        if (null === $configuration = $this->getConfiguration($configs, $container)) {
            return;
        }

        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias('api_platform_ms.http_client', $config['http_client']);

        $container->setParameter('api_platform_ms.name', $config['name']);
        $container->setParameter('api_platform_ms.hosts', $config['hosts']);
        $container->setParameter('api_platform_ms.microservices', $config['microservices']);
    }
}
