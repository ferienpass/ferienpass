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
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class PersonalDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'tl_member.firstname.0',
                'translation_domain' => 'contao_tl_member',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'tl_member.lastname.0',
                'translation_domain' => 'contao_tl_member',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'tl_member.email.0',
                'translation_domain' => 'contao_tl_member',
                'attr' => [
                    'placeholder' => 'email@beispiel.de',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'tl_member.phone.0',
                'translation_domain' => 'contao_tl_member',
                'required' => false,
                'constraints' => [
                    new PhoneNumber(['defaultRegion' => 'DE']),
                ],
            ])
            ->add('mobile', TextType::class, [
                'label' => 'tl_member.mobile.0',
                'translation_domain' => 'contao_tl_member',
                'required' => false,
                'constraints' => [
                    new PhoneNumber(['defaultRegion' => 'DE', 'type' => PhoneNumber::MOBILE]),
                ],
            ])
//            ->add('public_fields', ChoiceType::class, [
//                'label' => 'tl_member.public_fields.0',
//                'choices' => [
//                    'E-Mail-Adresse' => 'email',
//                    'Telefonnummer' => 'phone',
//                    'Mobil' => 'mobile',
//                ],
//                'translation_domain' => 'contao_tl_member',
//                'help' => 'tl_member.public_fields.1',
//                'required' => false,
//                'expanded' => true,
//                'multiple' => true,
//                'constraints' => [
//                ],
//            ])
        ;

        $builder
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
