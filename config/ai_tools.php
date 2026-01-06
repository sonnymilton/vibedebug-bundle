<?php

use Milton\VibedebugBundle\Agent\Tool\ProfileExporter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\{param, service};

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $containerConfigurator->parameters()->set('vibedebug.mate.profiler_storage.dsn', 'file:%mate.root_dir%/var/cache/dev/profiler');

    $services->set('vibedebug.mate.profiler.storage', FileProfilerStorage::class)
        ->arg('$dsn',  param('vibedebug.mate.profiler_storage.dsn'))
    ;

    $services->set('vibedebug.mate.profiler', Profiler::class)
        ->arg('$storage', service('vibedebug.mate.profiler.storage'))
    ;

    $services->set(ProfileExporter::class)
        ->arg('$profiler', service('vibedebug.mate.profiler'))
    ;
};
