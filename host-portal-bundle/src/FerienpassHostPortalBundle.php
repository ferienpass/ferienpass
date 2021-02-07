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

namespace Ferienpass\HostPortalBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FerienpassHostPortalBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterFragmentsPass(FragmentReference::TAG_NAME));
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
