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

namespace Ferienpass\CmsBundle\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(priority: -90)]
class BackendAdminListener
{
    public function __construct(private readonly Security $security, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $tree = $event->getTree();

        if ('headerMenu' !== $tree->getName()) {
            return;
        }

        $tree->removeChild('manual');
        $tree->removeChild('favorite');
        $tree->removeChild('alerts');

        $tree->getChild('preview')?->setLabel('Frontend');

        $admin = $event->getFactory()
            ->createItem('admin')
            ->setLabel('Ferienpass-Admin')
            ->setUri($this->urlGenerator->generate('admin_index'))
        ;

        $tree->addChild($admin);

        (new MenuManipulator())->moveToPosition($admin, $tree->count() - 3);
    }
}
