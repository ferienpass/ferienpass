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

namespace Ferienpass\HostPortalBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class MenuEvent extends \Contao\CoreBundle\Event\MenuEvent
{
    private array $options;

    public function __construct(FactoryInterface $factory, ItemInterface $tree, array $options)
    {
        parent::__construct($factory, $tree);

        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
