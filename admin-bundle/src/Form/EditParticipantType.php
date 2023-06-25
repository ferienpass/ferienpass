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

namespace Ferienpass\AdminBundle\Form;

use Ferienpass\AdminBundle\Dto\Annotation\EntityType;
use Ferienpass\CoreBundle\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditParticipantType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'label_format' => 'participants.label.%name%',
            'translation_domain' => 'admin',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['fieldset_group' => 'base', 'width' => '1/2'])
            ->add('lastname', null, ['fieldset_group' => 'base', 'width' => '1/2'])
//            ->add('parent', EntityType::class, [ 'class' => User::class ])
            ->add('dateOfBirth', BirthdayType::class, ['widget' => 'single_text', 'fieldset_group' => 'age', 'width' => '1/3'])
            ->add('email', EmailType::class, ['fieldset_group' => 'contact', 'width' => '1/2', 'help' => 'participants.help.email'])
            ->add('mobile', null, ['fieldset_group' => 'contact', 'width' => '1/2', 'help' => 'participants.help.mobile'])
            ->add('phone', null, ['fieldset_group' => 'contact', 'width' => '1/2'])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }
}
