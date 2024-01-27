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

namespace Ferienpass\AdminBundle\Form\Filter;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class FilterRegistry
{
    private array $filters;

    public function __construct(#[TaggedIterator('ferienpass_admin.filter', defaultIndexMethod: 'getEntity')] iterable $filters)
    {
        $this->filters = $filters instanceof \Traversable ? iterator_to_array($filters) : $filters;
    }

    public function byEntity(string $entityClass): ?AbstractFilter
    {
        return $this->filters[$entityClass] ?? null;
    }
}
