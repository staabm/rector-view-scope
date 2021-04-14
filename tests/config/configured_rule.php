<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Rector\Core\Configuration\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $parameters = $containerConfigurator->parameters();

    $services->set(\ViewScopeRector\ViewScopeRector::class);

    $parameters->set(Option::AUTOLOAD_PATHS, [ __DIR__.'/../../myautoload.php']);
};
