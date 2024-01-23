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

namespace Ferienpass\AdminBundle\Breadcrumb;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Breadcrumb
{
    public function __construct(private readonly FactoryInterface $factory, private readonly RequestStack $requestStack)
    {
    }

    public function generate(...$items): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        foreach (array_filter($items) as $item) {
            $this->addChild($menu, ...(array) $item);
        }

        return $menu;
    }

    private function addChild(ItemInterface $menu, string $name, array $options = []): void
    {
        $child = $this->factory->createItem($name, $options);
        $child->setCurrent($this->isCurrent($child));

        $menu->addChild($child);
    }

    private function isCurrent(ItemInterface $item): bool
    {
        return null === $item->getUri() || $item->getUri() === $this->requestStack->getCurrentRequest()?->getRequestUri();
    }
}
