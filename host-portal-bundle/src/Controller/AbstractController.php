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

namespace Ferienpass\HostPortalBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController as ContaoAbstractController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\PageModel;
use Ferienpass\HostPortalBundle\Page\PageBuilder;
use Ferienpass\HostPortalBundle\Page\PageBuilderFactory;
use Symfony\Component\Security\Core\Security;

class AbstractController extends ContaoAbstractController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services[PageBuilderFactory::class] = PageBuilderFactory::class;
        $services['security.helper'] = Security::class;

        return $services;
    }

    protected function checkToken(): void
    {
        if (!$this->container->get('security.helper')->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, 1)) {
            throw new AccessDeniedException('Access denied');
        }
    }

    protected function createPageBuilder(PageModel $pageModel): PageBuilder
    {
        return $this->container->get(PageBuilderFactory::class)->create($pageModel);
    }
}
