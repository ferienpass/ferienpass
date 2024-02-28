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

use Ferienpass\AdminBundle\Form\CompoundType\EditionTasksType;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Host;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditEditionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Edition::class,
            'label_format' => 'editions.label.%name%',
            'translation_domain' => 'admin',
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['fieldset_group' => 'base', 'width' => '1/2'])
            ->add('alias', null, ['fieldset_group' => 'base', 'width' => '1/2', 'help' => 'editions.help.alias'])
            ->add('tasks', EditionTasksType::class, ['fieldset_group' => 'tasks', 'label' => false])
            ->add('archived', CheckboxType::class, ['fieldset_group' => 'archived', 'help' => 'editions.help.archived'])
            ->add('hosts', EntityType::class, [
                'fieldset_group' => 'hosts',
                'class' => Host::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'help' => 'editions.help.hosts',
                'autocomplete' => true,
            ])
            ->add('hostsCanAssign', CheckboxType::class, ['fieldset_group' => 'hosts', 'help' => 'editions.help.hostsCanAssign'])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }
}
