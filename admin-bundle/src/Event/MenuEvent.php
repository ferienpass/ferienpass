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

namespace Ferienpass\AdminBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class MenuEvent extends \Contao\CoreBundle\Event\MenuEvent
{
    public function __construct(FactoryInterface $factory, ItemInterface $tree, private array $options)
    {
        parent::__construct($factory, $tree);
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
