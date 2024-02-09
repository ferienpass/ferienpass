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
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Notification;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditNotificationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notification::class,
            'label_format' => 'notifications.label.%name%',
            'translation_domain' => 'admin',
            'required' => false,
        ]);

        $resolver
            ->setDefined('notification_type')
            ->setRequired('notification_type')
            ->setDefined('supports_email')
            ->setDefault('supports_email', true)
            ->setDefined('supports_sms')
            ->setDefault('supports_sms', false)
            ->setDefined('new_edition')
            ->setDefault('new_edition', false)
            ->setDefined('can_delete')
            ->setDefault('can_delete', false)
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['new_edition']) {
            $builder
                ->add('edition', EntityType::class, [
                    'class' => Edition::class,
                    'query_builder' => fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('e')
                        ->where('e NOT IN (SELECT IDENTITY(n.edition) FROM '.Notification::class.' n WHERE IDENTITY(n.edition) IS NOT NULL AND n.type = :type)')
                        ->andWhere('e.archived = 0')
                        ->setParameter('type', $options['notification_type'])
                        ->orderBy('e.createdAt', 'DESC'),
                    'choice_label' => 'name',
                    'multiple' => false,
                    'expanded' => false,
                    'fieldset_group' => 'edition',
                    'required' => true,
                    'placeholder' => '-',
                ])
            ;
        }

        if ($options['supports_email']) {
            $builder
                ->add('emailSubject', null, ['fieldset_group' => 'email', 'width' => '2/3'])
                ->add('emailText', TextareaType::class, [
                    'attr' => ['rows' => 5],
                    'fieldset_group' => 'email',
                    'help' => 'notifications.help.emailText',
                ])
            ;
        }

        if ($options['supports_sms']) {
            $builder->add('smsText', TextareaType::class, ['attr' => ['rows' => 2], 'fieldset_group' => 'sms', 'help' => 'notifications.help.smsText']);
        }

        $builder->add('disabled', CheckboxType::class, ['fieldset_group' => 'disable', 'help' => 'notifications.help.disable']);

        $builder->add('submit', SubmitType::class);

        if ($options['can_delete']) {
            $builder->add('delete', SubmitType::class);
        }
    }
}
