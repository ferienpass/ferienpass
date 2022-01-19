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

namespace Ferienpass\HostPortalBundle\Form;

use AdamQuaile\Bundle\FieldsetBundle\Form\FieldsetType;
use Contao\Config;
use Contao\Controller;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\OfferCategory;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\HostPortalBundle\Form\CompoundType\DatesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditOfferType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'is_variant' => true,
            'csrf_protection' => false,
        ]);

        $resolver->addAllowedTypes('is_variant', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        Controller::loadDataContainer('Offer');

        $builder
            ->add('name_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Offer.title_legend',
                'help' => 'Offer.title_help',
                'fields' => function (FormBuilderInterface $builder) use ($options) {
                    $builder
                        ->add('name', TextType::class, [
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.name.0',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('description', TextareaType::class, [
                            'disabled' => $options['is_variant'],
                            'required' => false,
                            'label' => 'Offer.description.0',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('meetingPoint', TextType::class, [
                            'disabled' => $options['is_variant'],
                            'required' => false,
                            'label' => 'Offer.meetingPoint.0',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('bring', TextType::class, [
                            'disabled' => $options['is_variant'],
                            'required' => false,
                            'label' => 'Offer.bring.0',
                            'translation_domain' => 'contao_Offer',
                        ])
                    ;

                    if (isset($GLOBALS['TL_DCA']['Offer']['fields']['category'])) {
                        $builder
                            ->add('categories', EntityType::class, [
                                'class' => OfferCategory::class,
                                'multiple' => true,
                                'choice_label' => 'name',
                                'required' => false,
                                'disabled' => $options['is_variant'],
                                'label' => 'Offer.category.0',
                                'translation_domain' => 'contao_Offer',
                            ])
                        ;
                    }
                },
            ])
            ->add('date_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Offer.date_legend',
                'help' => 'Offer.date_help',
                'fields' => function (FormBuilderInterface $builder) {
                    $builder
                        ->add('dates', DatesType::class, [
                            'label' => false,
                            'help' => 'Sie können eine zusätzliche Zeit eintragen, wenn die gleiche Gruppe von Kindern an mehreren Terminen erscheinen muss. Wenn Sie das Angebot mehrmals anbieten, verwenden Sie stattdessen die Kopierfunktion auf der Übersichtsseite.',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('applicationDeadline', DateType::class, [
                            'required' => false,
                            'label' => 'Offer.applicationDeadline.0',
                            'help' => 'Offer.applicationDeadline.1',
                            'translation_domain' => 'contao_Offer',
                            'input_format' => Config::get('dateFormat'),
                            'widget' => 'single_text',
                        ])
                        ->add('comment', TextType::class, [
                            'required' => false,
                            'label' => 'Offer.comment.0',
                            'help' => 'Offer.comment.1',
                            'translation_domain' => 'contao_Offer',
                        ])
                    ;
                },
            ])
            ->add('applications_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Offer.applications_legend',
                'help' => 'Offer.applications_help',
                'fields' => function (FormBuilderInterface $builder) use ($options) {
                    $builder
                        ->add('minParticipants', IntegerType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.minParticipants.0',
                            'attr' => ['placeholder' => '-'],
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('maxParticipants', IntegerType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.maxParticipants.0',
                            'attr' => ['placeholder' => 'ohne Begrenzung'],
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('minAge', IntegerType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.minAge.0',
                            'attr' => ['placeholder' => 'kein Mindestalter'],
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('maxAge', IntegerType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.maxAge.0',
                            'attr' => ['placeholder' => 'kein Höchstalter'],
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('requiresApplication', CheckboxType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.requiresApplication.0',
                            'help' => 'Offer.requiresApplication.1',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('onlineApplication', CheckboxType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.onlineApplication.0',
                            'help' => 'Offer.onlineApplication.1',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('applyText', TextType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.applyText.0',
                            'help' => 'Offer.applyText.1',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('contact', TextType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.contact.0',
                            'help' => 'Offer.contact.1',
                            'translation_domain' => 'contao_Offer',
                        ])
                        ->add('fee', MoneyType::class, [
                            'required' => false,
                            'disabled' => $options['is_variant'],
                            'label' => 'Offer.fee.0',
                            'translation_domain' => 'contao_Offer',
                            'divisor' => 100,
                            'html5' => true,
                        ])
                    ;
                    if (isset($GLOBALS['TL_DCA']['Offer']['fields']['aktivPass'])) {
                        $builder
                            ->add('aktivPass', CheckboxType::class, [
                                'required' => false,
                                'disabled' => $options['is_variant'],
                                'label' => 'Offer.aktivPass.0',
                                'help' => 'Offer.aktivPass.1',
                                'translation_domain' => 'contao_Offer',
                            ])
                        ;
                    }

                    if (isset($GLOBALS['TL_DCA']['Offer']['fields']['accessibility'])) {
                        $builder
                            ->add('accessibility', ChoiceType::class, [
                                'required' => false,
                                'disabled' => $options['is_variant'],
                                'label' => 'Offer.accessibility.0',
                                'choices' => [
                                    'barrierefrei',
                                    'koerperliches-handicap',
                                    'assistenz',
                                    'geistiges-handicap',
                                ],
                                'choice_label' => fn ($choice) => 'accessibility.'.$choice.'.label',
                                'expanded' => true,
                                'multiple' => true,
                            ])
                        ;
                    }
                },
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'disabled' => $options['is_variant'],
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '6Mi',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Folgende Dateiformate sind erlaubt: JPG, PNG',
                    ]),
                ],
            ])
            ->add('imgCopyright', TextType::class, [
                'mapped' => false,
                'disabled' => $options['is_variant'],
                'required' => false,
                'label' => 'tl_files.imgCopyright.0',
                'help' => 'tl_files.imgCopyright.1',
                'translation_domain' => 'contao_tl_files',
            ])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ]);
    }
}
