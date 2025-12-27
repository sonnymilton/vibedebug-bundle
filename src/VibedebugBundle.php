<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class VibedebugBundle extends AbstractBundle
{
    /**
     * @param array{} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasExtension('web_profiler')) {
            throw new InvalidConfigurationException('VibedebugBundle requires symfony/web-profiler-bundle to be installed and enabled.');
        }

        $container->import(__DIR__.'/../config/services.yaml');
    }
}
