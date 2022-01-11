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

use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\HostPortalBundle\Dto\Annotation\FormType as FormTypeAnnotation;
use Ferienpass\HostPortalBundle\Dto\EditHostDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        $properties = (new \ReflectionClass($options['data_class']))->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
          $annotations = array_merge(...array_map(fn (\ReflectionAttribute $attribute) => $attribute->getArguments(), $property->getAttributes(FormTypeAnnotation::class)));
            $group = current($annotations);

            $fieldOptions = [
                'label' => "Host.{$property->getName()}.0",
                'required' => 'name' === $property->getName(),
                'help' => ($annotations['showHelp'] ?? false) ? "Host.{$property->getName()}.1" : null,
                'translation_domain' => 'contao_Host',
                'fieldset_group' => $group,
            ];

            if ($placeholder = $annotations['placeholder'] ?? null) {
                $fieldOptions += ['attr' => ['placeholder' => $placeholder]];
            }

            $builder->add($property->getName(), null, $fieldOptions);
        }

        $builder
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
            ])
        ;
    }
}
