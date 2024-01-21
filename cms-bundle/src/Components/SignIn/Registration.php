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

namespace Ferienpass\CmsBundle\Components\SignIn;

use Contao\MemberModel;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Form\UserRegistrationType;
use Ferienpass\CoreBundle\Message\AccountCreated;
use Ferienpass\CoreBundle\Message\AccountResendActivation;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Registration extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public User $initialFormData;

    public function __construct(private readonly ParticipantRepository $participantRepository)
    {
        $this->initialFormData = new User();
    }

    #[LiveAction]
    public function submit(Session $session, EntityManagerInterface $em, MessageBusInterface $messageBus, UserPasswordHasherInterface $passwordHasher)
    {
        $this->submitForm();

        /** @var User $user */
        $user = $this->getForm()->getData();

        $user->setRoles(['ROLE_MEMBER']);

        dd($user);

        if (false) {
            $this->resendActivationMail($unactivated);

            return $this->redirectToRoute('registration_confirm');
        }

        if (false) {
            $form->addError(
                new FormError('Ein Konto mit dieser E-Mail-Adresse besteht bereits. Versuchen Sie sich, anzumelden oder Ihr Passwort zurückzusetzen.')
            );
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));

        $user->setDisabled();

        $session->set('registration.email', $user->getEmail());
        $em->persist($user);

        $this->migrateSessionParticipants($session, $user);

        $em->flush();

        $messageBus->dispatch(new AccountCreated($user->getId()));

        return $this->redirectToRoute('registration_confirm');
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(UserRegistrationType::class, $this->initialFormData);
    }

    private function resendActivationMail(MemberModel $member): void
    {
        if (!$member->disable) {
            return;
        }

        $this->messageBus->dispatch(new AccountResendActivation((int) $member->id));

        $this->addFlash(...Flash::confirmation()->text(new TranslatableMessage('MSC.resendActivation', [], 'contao_default'))->create());
    }

    private function migrateSessionParticipants(Session $session, User $user)
    {
        $ids = $session->get('participant_ids', []);
        if (0 === \count($ids)) {
            return;
        }

        foreach ($this->participantRepository->findBy(['id' => $ids]) as $participant) {
            /** @var Participant $participant */
            if (null === $participant) {
                continue;
            }

            $participant->setUser($user);
        }
    }
}
