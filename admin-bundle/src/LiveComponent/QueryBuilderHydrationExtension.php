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

namespace Ferienpass\AdminBundle\LiveComponent;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class QueryBuilderHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function supports(string $className): bool
    {
        return is_a($className, QueryBuilder::class, true);
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        $qb = new QueryBuilder($this->doctrine->getManager());

        foreach ($value as $k => $v) {
            $qb->add($k, $v);
        }

        return $qb;
    }

    public function dehydrate(object $object): mixed
    {
        /** @var QueryBuilder $object */
        return array_map(fn ($part) => \is_array($part) && !empty($part) ? array_map('strval', $part) : $part, array_filter($object->getDQLParts()));
    }
}
