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

use Contao\Config;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'offer_date';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('begin', DateTimeType::class, [
                'label' => false,
                'html5' => false,
                'date_widget' => 'single_text',
                'date_format' => 'dd.MM.yyyy',
                'input_format' => Config::get('datimFormat'),
                'minutes' => [0, 15, 30, 45],
            ])
            ->add('end', DateTimeType::class, [
                'label' => false,
                'html5' => false,
                'date_format' => 'dd.MM.yyyy',
                'date_widget' => 'single_text',
                'input_format' => Config::get('datimFormat'),
                'minutes' => [0, 15, 30, 45],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfferDate::class,
            // We rely on the fact that the parent form is a DatesType
            // and its parent form is a OfferType that is linked to an Offer entity.
            'empty_data' => fn (FormInterface $form) => new OfferDate($form->getParent()->getParent()->getData()->offerEntity()),
        ]);
    }
}
