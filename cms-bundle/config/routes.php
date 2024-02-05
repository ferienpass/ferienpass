<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/Frontend/', type: 'annotation',);
    $routes->import('../src/Controller/Page/', type: 'annotation');
    $routes->import('../src/Components/', type: 'annotation');
};
