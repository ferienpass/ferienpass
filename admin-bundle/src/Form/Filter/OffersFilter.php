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

use Doctrine\ORM\QueryBuilder;
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersFilter extends AbstractFilter
{
    public function __construct(#[TaggedIterator('ferienpass_admin.filter.offer', indexAttribute: 'key')] iterable $filterTypes, private readonly Security $security)
    {
        $this->filterTypes = $filterTypes instanceof \Traversable ? iterator_to_array($filterTypes) : $filterTypes;
    }

    public static function getEntity(): string
    {
        return Offer::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'offers.filter.%name%',
        ]);
    }

    protected function getSorting(): array
    {
        $return = [];
        $return[] = [
            'date' => fn (QueryBuilder $qb) => $qb->leftJoin('i.dates', 'd')->addOrderBy('d.begin', 'ASC'),
            'name' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.name', 'ASC'),
        ];

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $return[] = [
                'host' => fn (QueryBuilder $qb) => $qb->leftJoin('i.hosts', 'h')->addOrderBy('h.name', 'ASC'),
                'status' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.state', 'ASC')->addOrderBy('i.name', 'ASC'),
            ];
        }

        return array_merge(...$return);
    }
}
