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

use Ferienpass\AdminBundle\Dto\Annotation\FormType as FormTypeAnnotation;
use Ferienpass\AdminBundle\Form\CompoundType\OfferDatesType;
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditOfferType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'is_variant' => true,
            'label_format' => 'offers.label.%name%',
            'translation_domain' => 'admin',
        ]);

        $resolver->addAllowedTypes('is_variant', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['fieldset_group' => 'base', 'width' => '2/3'])
            ->add('description', TextareaType::class, ['fieldset_group' => 'base'])
//            ->add('parent', EntityType::class, [ 'class' => User::class ])
            // ->add('categories', EntityType::class, ['class' => OfferCategory::class, 'choice_label' => 'name', 'fieldset_group' => 'base'])
            ->add('dates', OfferDatesType::class, ['help' => 'Sie können eine zusätzliche Zeit eintragen, wenn die gleiche Gruppe von Kindern an mehreren Terminen erscheinen muss. Wenn Sie das Angebot mehrmals anbieten, verwenden Sie stattdessen die Kopierfunktion auf der Übersichtsseite.', 'fieldset_group' => 'dates'])
            ->add('applicationDeadline', DateType::class, ['help' => 'offers.help.applicationDeadline', 'input_format' => 'd.m.Y', 'widget' => 'single_text', 'fieldset_group' => 'dates', 'width' => '1/3'])
            ->add('minAge', IntegerType::class, ['attr' => ['placeholder' => 'kein Mindestalter'], 'fieldset_group' => 'details', 'width' => '1/3'])
            ->add('maxAge', IntegerType::class, ['attr' => ['placeholder' => 'kein Höchstalter'], 'fieldset_group' => 'details', 'width' => '1/3'])
            ->add('meetingPoint', null, ['fieldset_group' => 'details', 'width' => '1/2'])
            ->add('bring', null, ['fieldset_group' => 'details', 'width' => '1/2'])
            ->add('fee', MoneyType::class, ['divisor' => 100, 'html5' => true, 'fieldset_group' => 'details', 'width' => '1/3'])
            ->add('wheelchairAccessible', ChoiceType::class, ['choices' => ['Ja' => true, 'Nein' => false], 'placeholder' => 'Nach Absprache', 'expanded' => false, 'multiple' => false, 'fieldset_group' => 'details', 'width' => '1/3'])
            ->add('requiresApplication', CheckboxType::class, ['help' => 'offers.help.requiresApplication', 'fieldset_group' => 'applications', 'width' => '1/2'])
            ->add('onlineApplication', CheckboxType::class, ['help' => 'offers.help.onlineApplication', 'fieldset_group' => 'applications', 'width' => '1/2'])
            ->add('minParticipants', IntegerType::class, ['attr' => ['placeholder' => '-'], 'fieldset_group' => 'applications', 'width' => '1/3'])
            ->add('maxParticipants', IntegerType::class, ['attr' => ['placeholder' => 'ohne Begrenzung'], 'fieldset_group' => 'applications', 'width' => '1/3'])
            ->add('applyText', null, ['help' => 'offers.help.applyText', 'fieldset_group' => 'applications', 'width' => '1/2'])
            ->add('contact', null, ['help' => 'offers.help.contact', 'fieldset_group' => 'applications', 'width' => '1/2'])
            ->add('image', null, ['fieldset_group' => 'media'])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;

        $properties = (new \ReflectionClass($options['data_class']))->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $annotations = array_merge(...array_map(fn (\ReflectionAttribute $attribute) => $attribute->getArguments(), $property->getAttributes(FormTypeAnnotation::class)));

            $fieldOptions = [
                'disabled' => $options['is_variant'],
                'label' => sprintf('Offer.%s.0', $property->getName()),
                'required' => 'name' === $property->getName(),
                'translation_domain' => 'contao_Offer',
            ];

            if (!isset($fieldOptions['help']) && ($annotations['showHelp'] ?? false)) {
                $fieldOptions['help'] = sprintf('Offer.%s.1', $property->getName());
            }

            if ($placeholder = $annotations['placeholder'] ?? null) {
                $fieldOptions['attr']['placeholder'] = $placeholder;
            }

            $builder->add($property->getName(), null, $fieldOptions);
        }

        //        $builder
        //            ->add('image', FileType::class, [
        //                'mapped' => false,
        //                'disabled' => $options['is_variant'],
        //                'required' => false,
        //                'constraints' => [
        //                    new File([
        //                        'maxSize' => '6Mi',
        //                        'mimeTypes' => [
        //                            'image/jpeg',
        //                            'image/png',
        //                        ],
        //                        'mimeTypesMessage' => 'Folgende Dateiformate sind erlaubt: JPG, PNG',
        //                    ]),
        //                ],
        //            ])
        //            ->add('imgCopyright', TextType::class, [
        //                'mapped' => false,
        //                'disabled' => $options['is_variant'],
        //                'required' => false,
        //                'label' => 'tl_files.imgCopyright.0',
        //                'help' => 'tl_files.imgCopyright.1',
        //                'translation_domain' => 'contao_tl_files',
        //            ])
        //            ->add('request_token', ContaoRequestTokenType::class)
        //            ->add('submit', SubmitType::class, [
        //                'label' => 'Daten speichern',
        //            ])
        //        ;
    }
}
