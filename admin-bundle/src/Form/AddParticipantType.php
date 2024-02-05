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

use Ferienpass\CmsBundle\Form\SimpleType\ContaoRequestTokenType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

class AddParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Vorname',
            ])
            ->add('lastname', TextType::class, [
                'required' => false,
                'label' => 'Nachname',
            ])
            ->add('dateOfBirth', BirthdayType::class, [
                'required' => false,
                'label' => 'Geburtsdatum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'placeholder' => 'tt.mm.jjjj',
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'tl_member.email.0',
                'translation_domain' => 'contao_tl_member',
                'help' => 'Für Benachrichtigungen per E-Mail',
                'attr' => [
                    'placeholder' => 'email@beispiel.de',
                ],
                'constraints' => [
                    new Email(),
                ],
            ])
            ->add('mobile', TelType::class, [
                'label' => 'tl_member.mobile.0',
                'translation_domain' => 'contao_tl_member',
                'help' => 'Für Benachrichtigungen per SMS',
                'constraints' => [
                    new PhoneNumber(['type' => PhoneNumber::MOBILE, 'defaultRegion' => 'DE']),
                ],
                'attr' => [
                    'placeholder' => '0172-0000000',
                ],
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'tl_member.phone.0',
                'translation_domain' => 'contao_tl_member',
                'constraints' => [
                    new PhoneNumber(['type' => PhoneNumber::FIXED_LINE, 'defaultRegion' => 'DE']),
                ],
                'attr' => [
                    'placeholder' => '030-00000',
                ],
                'required' => false,
            ])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, ['label' => 'Einladen'])
        ;
    }
}
