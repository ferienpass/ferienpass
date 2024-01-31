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
use Ferienpass\CoreBundle\Entity\Host;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HostsFilter extends AbstractFilter
{
    public static function getEntity(): string
    {
        return Host::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'accounts.filter.%name%',
        ]);
    }

    protected static function getSorting(): array
    {
        return [
            'createdAt' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.createdAt', 'DESC'),
            'name' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.name', 'ASC'),
            'numberOffers' => fn (QueryBuilder $qb) => $qb->addSelect('COUNT(o) AS HIDDEN countOffers')->leftJoin('i.offers', 'o')->addGroupBy('i')->addOrderBy('countOffers', 'DESC'),
        ];
    }
}
