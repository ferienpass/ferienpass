<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\FixturesBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Ferienpass\FixturesBundle\DependencyInjection\FerienpassFixturesExtension;
use Ferienpass\FixturesBundle\FerienpassFixturesBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

final class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ZenstruckFoundryBundle::class),
            BundleConfig::create(FerienpassFixturesBundle::class),
        ];
    }
}
