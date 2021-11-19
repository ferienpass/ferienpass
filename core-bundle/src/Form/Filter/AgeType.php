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

namespace Ferienpass\CoreBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;

class AgeType extends AbstractFilterType
{
    public function getParent(): string
    {
        return IntegerType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $qb
            ->andWhere($qb->expr()->andX('o.minAge IS NULL OR o.minAge = 0 OR o.minAge <= :q_age', 'o.maxAge IS NULL OR o.maxAge = 0 OR o.maxAge >= :q_age'))
            ->setParameter('q_age', $form->getData())
        ;
    }
}
