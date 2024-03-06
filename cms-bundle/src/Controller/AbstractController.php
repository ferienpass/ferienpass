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

namespace Ferienpass\CmsBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController as ContaoAbstractController;
use Contao\PageModel;
use Ferienpass\CmsBundle\Page\PageBuilder;
use Ferienpass\CmsBundle\Page\PageBuilderFactory;

class AbstractController extends ContaoAbstractController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[PageBuilderFactory::class] = PageBuilderFactory::class;

        return $services;
    }

    protected function checkToken(): void
    {
        $this->denyAccessUnlessGranted('ROLE_MEMBER');
    }

    protected function createPageBuilder(PageModel $pageModel): PageBuilder
    {
        return $this->container->get(PageBuilderFactory::class)->create($pageModel);
    }
}
