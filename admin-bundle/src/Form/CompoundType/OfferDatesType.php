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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class OfferDatesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => DateRangeType::class,
            'entry_options' => [
                'label' => false,
                'data_class' => OfferDate::class,
                'empty_data' => function (FormInterface $form): OfferDate {
                    return new OfferDate($form->getParent()->getParent()->getData());
                },
            ],
            'error_bubbling' => true,
        ]);
    }

    public function getParent(): string
    {
        return LiveCollectionType::class;
    }
}
