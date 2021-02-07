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
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\HostPortalBundle\Dto\EditHostDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditHostType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EditHostDto::class,
            'csrf_protection' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Host.name_legend',
                'help' => 'Host.name_help',
                'fields' => function (FormBuilderInterface $builder) {
                    $builder
                        ->add('name', TextType::class, [
                            'label' => 'Host.name.0',
                            'translation_domain' => 'contao_Host',
                        ])
                        ->add('text', TextareaType::class, [
                            'required' => false,
                            'label' => 'Host.text.0',
                            'translation_domain' => 'contao_Host',
                        ])
                    ;
                },
            ])
            ->add('contact_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Host.contact_legend',
                'help' => 'Host.contact_help',
                'fields' => function (FormBuilderInterface $builder) {
                    $builder
                        ->add('phone', TextType::class, [
                            'required' => false,
                            'label' => 'Host.phone.0',
                            'translation_domain' => 'contao_Host',
                        ])
                        ->add('email', EmailType::class, [
                            'required' => false,
                            'label' => 'Host.email.0',
                            'translation_domain' => 'contao_Host',
                        ])
                        ->add('website', UrlType::class, [
                            'required' => false,
                            'default_protocol' => 'https',
                            'label' => 'Host.website.0',
                            'translation_domain' => 'contao_Host',
                        ])
                    ;
                },
            ])
            ->add('address_fieldset', FieldsetType::class, [
                'label' => false,
                'legend' => 'Host.address_legend',
                'help' => 'Host.address_help',
                'fields' => function (FormBuilderInterface $builder) {
                    $builder
                        ->add('street', TextType::class, [
                            'required' => false,
                            'label' => 'Host.street.0',
                            'translation_domain' => 'contao_Host',
                        ])
                        ->add('postal', TextType::class, [
                            'required' => false,
                            'label' => 'Host.postal.0',
                            'translation_domain' => 'contao_Host',
                        ])
                        ->add('city', TextType::class, [
                            'required' => false,
                            'label' => 'Host.city.0',
                            'translation_domain' => 'contao_Host',
                        ])
                    ;
                },
            ])
            ->add('logo', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/svg+xml',
                            'image/png',
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Folgende Dateiformate sind für Logos erlaubt: JPG, PNG, SVG, PDF',
                    ]),
                ],
            ])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ]);
    }
}
