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

use Richardhj\ContaoKnpMenuBundle\Event\FrontendMenuEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Security;

#[AsEventListener]
class FrontendProtectedListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(FrontendMenuEvent $event)
    {
        if ($this->security->isGranted('ROLE_MEMBER')) {
            return;
        }

        $tree = $event->getTree();

        foreach ($tree->getChildren() as $name => $item) {
            if (\in_array($item->getExtra('type'), ['applications', 'user_account', 'notifications'], true)) {
                $tree->removeChild($name);
            }
        }
    }
}
