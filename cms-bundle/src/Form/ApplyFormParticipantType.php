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

namespace Ferienpass\CmsBundle\Form;

use Ferienpass\CmsBundle\Form\CompundType\ParticipantType;
use Ferienpass\CmsBundle\Form\SimpleType\ContaoRequestTokenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplyFormParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('participant', ParticipantType::class, ['access_code' => $options['access_code']])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, ['label' => 'Speichern und weiter'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('access_code');
        $resolver->setDefaults([
            'access_code' => false,
            'csrf_protection' => false,
        ]);
    }
}
