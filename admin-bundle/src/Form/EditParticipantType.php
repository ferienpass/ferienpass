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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditParticipantType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('show_submit');

        $resolver->setDefaults([
            'data_class' => Participant::class,
            'label_format' => 'participants.label.%name%',
            'translation_domain' => 'admin',
            'required' => false,
            'show_submit' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['required' => true, 'fieldset_group' => 'base', 'width' => '1/2'])
            ->add('lastname', null, ['fieldset_group' => 'base', 'width' => '1/2'])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.lastname', 'ASC');
                },
                'choice_label' => 'name',
                'fieldset_group' => 'base',
                'placeholder' => '-',
                'width' => '2/3',
                'help' => 'participants.help.user',
            ])
            ->add('dateOfBirth', BirthdayType::class, ['widget' => 'single_text', 'fieldset_group' => 'age', 'width' => '1/3'])
            ->add('ownEmail', EmailType::class, ['fieldset_group' => 'contact', 'width' => '1/2', 'help' => 'participants.help.email'])
            ->add('ownMobile', null, ['fieldset_group' => 'contact', 'width' => '1/2', 'help' => 'participants.help.mobile'])
            ->add('ownPhone', null, ['fieldset_group' => 'contact', 'width' => '1/2'])
            ->add('discounted', CheckboxType::class, ['fieldset_group' => 'allowance', 'help' => 'participants.help.discounted'])
        ;

        if ($options['show_submit']) {
            $builder->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ]);
        }
    }
}
