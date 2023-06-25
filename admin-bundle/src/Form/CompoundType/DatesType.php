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

namespace Ferienpass\AdminBundle\Form\CompoundType;

use Ferienpass\CoreBundle\Entity\OfferDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatesType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'offer_dates';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => DateType::class,
            'entry_options' => ['label' => false],
            'allow_add' => 'true',
            'allow_delete' => 'true',
            'delete_empty' => fn (OfferDate $date = null) => null === $date || (null === $date->getBegin() && null === $date->getEnd()),
            'by_reference' => false,
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }
}
