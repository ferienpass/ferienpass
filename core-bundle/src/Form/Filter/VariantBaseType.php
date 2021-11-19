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
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VariantBaseType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('class', Offer::class);
        $resolver->setDefault('choice_label', 'name');
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $qb
            ->andWhere($qb->expr()->orX()->add('o.id = :q_vbase')->add('o.variantBase = :q_vbase'))
            ->setParameter('q_vbase', $form->getData())
        ;
    }
}
