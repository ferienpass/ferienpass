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

namespace Ferienpass\CmsBundle\EventListener\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Event\RouterGenerateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Generates the URL for a Contao page by its page type.
 */
#[AsEventListener('cmf_routing.pre_dynamic_generate')]
class PageUrlGeneratorListener
{
    public function __construct(private readonly ContaoFramework $contaoFramework)
    {
    }

    public function __invoke(RouterGenerateEvent $event)
    {
        $this->contaoFramework->initialize();

        if (null === $page = PageModel::findOneBy(["type=? AND published='1'"], [$event->getRoute()])) {
            return;
        }

        $event->setRoute('tl_page.'.$page->id);
    }
}
