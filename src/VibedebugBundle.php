<?php

declare(strict_types=1);

namespace Milton\VibedebugBundle;

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
        $container->import(__DIR__.'/../config/services.yaml');
    }
}
