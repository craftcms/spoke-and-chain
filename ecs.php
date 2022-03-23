<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function(ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::PATHS, [
        __DIR__ . '/modules',
        __FILE__,
    ]);

    $containerConfigurator->import(SetList::CRAFT_CMS_3); // for Craft 3 projects
};
