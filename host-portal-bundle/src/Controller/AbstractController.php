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
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\FrontendUser;
use Contao\PageModel;
use Ferienpass\HostPortalBundle\Page\PageBuilder;
use Ferienpass\HostPortalBundle\Page\PageBuilderFactory;

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
        $token = $this->get('security.token_storage')->getToken();
        if (null === $token || $this->get('security.authentication.trust_resolver')->isAnonymous($token)) {
            throw new InsufficientAuthenticationException('Not authenticated');
        }

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_MEMBER')) {
            throw new AccessDeniedException('Access denied');
        }

        /** @var FrontendUser $user */
        $user = $token->getUser();
        if (!$user->isMemberOf(1)) {
            throw new AccessDeniedException('Access denied');
        }
    }

    protected function createPageBuilder(PageModel $pageModel): PageBuilder
    {
        return $this->get(PageBuilderFactory::class)->create($pageModel);
    }
}
