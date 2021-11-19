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
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeeType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('divisor', 100);
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $qb
            ->andWhere('o.fee <= :q_fee')
            ->setParameter('q_fee', $form->getData())
        ;
    }
}
