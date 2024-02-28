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

namespace Ferienpass\CmsBundle\Form\CompundType;

use Ferienpass\CoreBundle\Entity\Participant;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ParticipantType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Vorname',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nachname',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('dateOfBirth', BirthdayType::class, [
                'label' => 'Geburtsdatum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'placeholder' => 'tt.mm.jjjj',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;

        $user = $this->security->getUser();
        if (!$user) {
            $builder->add('email', EmailType::class, [
                'label' => 'E-Mail-Adresse',
                'attr' => [
                    'placeholder' => 'email@beispiel.de',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ]);
        }

        if (!$user) {
            $builder->add('mobile', TelType::class, [
                'label' => 'Handynummer',
                'required' => false,
                'constraints' => [
                    new PhoneNumber(type: PhoneNumber::MOBILE, defaultRegion: 'DE'),
                ],
                'attr' => [
                    'placeholder' => '0172-0000000',
                ],
            ]);
        }

        if ($options['access_code']) {
            $builder->add('accessCode', TextType::class, [
                'label' => 'Zugangscode',
                'required' => true,
                'mapped' => false,
                'constraints' => [
                ],
                'attr' => [
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $user = $this->security->getUser();

        $resolver->setDefined('access_code');

        $resolver->setDefaults([
            'access_code' => false,
            'data_class' => Participant::class,
            'empty_data' => fn (FormInterface $form) => new Participant($user ?? null),
        ]);
    }
}
