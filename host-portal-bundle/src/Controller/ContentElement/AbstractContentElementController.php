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

namespace Ferienpass\HostPortalBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController as BaseContentElementController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractContentElementController extends BaseContentElementController
{
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getBackendWildcard(): Response
    {
        $name = $this->get('translator')->trans('CTE.'.$this->getType().'.0', [], 'contao_modules');

        $template = new BackendTemplate('be_wildcard');

        $template->wildcard = '### '.strtoupper($name).' ###';

        return new Response($template->parse());
    }
}
