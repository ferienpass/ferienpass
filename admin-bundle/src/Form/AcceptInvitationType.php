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

use Contao\FrontendUser;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class AcceptInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['data'] instanceof FrontendUser) {
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
                ->add('password', PasswordType::class, [
                    'label' => 'MSC.password',
                    'translation_domain' => 'contao_default',
                    'help' => 'Ihr Passwort muss aus mindestens 8 Zeichen bestehen.',
                    'constraints' => [
                        new NotBlank(),
                        new NotCompromisedPassword(),
                        new Length(['min' => 8]),
                    ],
                ])
            ;
        }

        $builder
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Einladung akzeptieren',
            ])
        ;
    }
}
