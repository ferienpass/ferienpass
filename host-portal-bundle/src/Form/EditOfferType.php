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
use Ferienpass\HostPortalBundle\Dto\EditOfferDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditOfferType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EditOfferDto::class,
            'is_variant' => true,
            'csrf_protection' => false,
        ]);

        $resolver->addAllowedTypes('is_variant', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $properties = (new \ReflectionClass($options['data_class']))->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $annotations = array_merge(...array_map(fn (\ReflectionAttribute $attribute) => $attribute->getArguments(), $property->getAttributes(FormTypeAnnotation::class)));
            $group = current($annotations);

            $fieldOptions = [
                'disabled' => $options['is_variant'],
                'label' => sprintf('Offer.%s.0', $property->getName()),
                'required' => 'name' === $property->getName(),
                'translation_domain' => 'contao_Offer',
                'fieldset_group' => $group,
            ];

            if (!isset($fieldOptions['help']) && ($annotations['showHelp'] ?? false)) {
                $fieldOptions['help'] = sprintf('Offer.%s.1', $property->getName());
            }

            if ($placeholder = $annotations['placeholder'] ?? null) {
                $fieldOptions['attr']['placeholder'] = $placeholder;
            }

            $builder->add($property->getName(), null, $fieldOptions);
        }

        $builder
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
            ])
        ;
    }
}
