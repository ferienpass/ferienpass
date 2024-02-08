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

use Ferienpass\CoreBundle\Entity\EditionTask;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class EditionTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('period', DateRangeType::class, [
                'field_begin' => 'periodBegin',
                'field_end' => 'periodEnd',
                'inherit_data' => true,
                'label' => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'holiday',
                    'host_editing_stage',
                    'application_system',
                    'allocation',
                    'pay_days',
                    'publish_lists',
                    'show_offers',
                    'custom',
                ],
                'placeholder' => '-',
                'choice_label' => function ($choice): TranslatableMessage|string {
                    return new TranslatableMessage('editions.task.'.$choice, [], 'admin');
                },
                'width' => '1/2',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();

            if (!($data = $event->getData()) instanceof EditionTask) {
                return;
            }

            if ('custom' === $data->getType()) {
                $form->add('title');
                $form->add('description');
            }

            if ('application_system' === $data->getType()) {
                $form->add('application_system', ChoiceType::class, [
                    'choices' => [
                        'lot',
                        'firstcome',
                    ],
                    'choice_label' => function ($choice): TranslatableMessage|string {
                        return new TranslatableMessage('MSC.application_system.'.$choice, [], 'contao_default');
                    },
                    'placeholder' => '-',
                ]);
            }

            if ($data->isAnApplicationSystem() && 'lot' === $data->getApplicationSystem()) {
                $form->add('max_applications', IntegerType::class, ['help' => 'editions.help.max_applications']);
                $form->add('skip_max_applications', CheckboxType::class, ['label' => 'editions.label.skip_max_applications', 'help' => 'editions.help.skip_max_applications']);
                $form->add('hide_status', CheckboxType::class, ['label' => 'editions.label.hide_status', 'help' => 'editions.help.hide_status']);
                $form->add('allow_anonymous', CheckboxType::class, ['label' => 'editions.label.allow_anonymous', 'help' => 'editions.help.allow_anonymous']);
            }

            if ($data->isAnApplicationSystem() && 'firstcome' === $data->getApplicationSystem()) {
                $form->add('max_applications_day', IntegerType::class, ['label' => 'editions.label.max_applications_day', 'help' => 'editions.help.max_applications_per_day']);
                $form->add('allow_anonymous', CheckboxType::class, ['label' => 'editions.label.allow_anonymous', 'help' => 'editions.help.allow_anonymous']);
            }

            if ($data->isAnApplicationSystem() && $data->isAllowAnonymous()) {
                $form->add('allowAnonymousFee', CheckboxType::class, ['label' => 'editions.label.allow_anonymous_fee', 'help' => 'editions.help.allow_anonymous_fee']);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EditionTask::class,
            'empty_data' => function (FormInterface $form): EditionTask {
                return new EditionTask($form->getParent()->getParent()->getData());
            },
        ]);
    }
}
