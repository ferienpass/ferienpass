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

namespace Ferienpass\CmsBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Ferienpass\CmsBundle\DependencyInjection\Compiler\UserAccountFragmentsPass;
use Ferienpass\CmsBundle\DependencyInjection\FerienpassCmsExtension;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FerienpassCmsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FerienpassCmsExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterFragmentsPass(FragmentReference::TAG_NAME));
        $container->addCompilerPass(new UserAccountFragmentsPass());
    }
}
