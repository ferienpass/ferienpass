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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LatestDateType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('widget', 'single_text');
    }

    public function getParent(): string
    {
        return DateType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $v = $form->getData();

        \assert($v instanceof \DateTime);
        // < DATE() +1 day has the same effect as <= DATE() 23:59:59
        $v->modify('+1 day');
        $qb
            ->andWhere($qb->expr()->orX()->add('dates IS NULL')->add('MAX(dates.end) <= :q_dend'))
            ->setParameter('q_dend', $v, Types::DATE_MUTABLE)
        ;
    }
}
