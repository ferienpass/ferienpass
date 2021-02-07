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

namespace Ferienpass\FixturesBundle;

use Ferienpass\FixturesBundle\DependencyInjection\FerienpassFixturesExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FerienpassFixturesBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension()
    {
        return new FerienpassFixturesExtension();
    }
}
