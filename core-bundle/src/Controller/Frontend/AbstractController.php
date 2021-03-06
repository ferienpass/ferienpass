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

namespace Ferienpass\CoreBundle\Controller\Frontend;

use Contao\CoreBundle\Controller\AbstractController as ContaoAbstractController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\FrontendUser;
use Contao\PageModel;
use Ferienpass\CoreBundle\Page\PageBuilder;
use Ferienpass\CoreBundle\Page\PageBuilderFactory;

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
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) {
            throw new InsufficientAuthenticationException('Not authenticated');
        }

        if (!$user->isMemberOf(2)) {
            throw new AccessDeniedException('Access denied');
        }
    }

    protected function createPageBuilder(PageModel $pageModel): PageBuilder
    {
        return $this->get(PageBuilderFactory::class)->create($pageModel);
    }
}
