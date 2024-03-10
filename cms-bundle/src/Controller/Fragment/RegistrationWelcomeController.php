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

namespace Ferienpass\CmsBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CmsBundle\Form\UserParticipantsType;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationWelcomeController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(UserParticipantsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var iterable<Participant> $participants */
            $participants = $form->get('participants')->getData();

            foreach ($participants as $participant) {
                $this->entityManager->persist($participant);
            }

            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmationModal()
                ->headline('Los geht\'s!')
                ->text('Nun können Sie loslegen und Ihre Kinder zu Ferienpass-Angeboten anmelden.')
                ->href($this->generateUrl('offer_list'))
                ->linkText('Zu den Angeboten')
                ->create()
            );

            return $this->redirectToRoute($request->attributes->get('_route') ?: 'personal_data');
        }

        return $this->render('@FerienpassCms/fragment/registration_welcome.html.twig', [
            'form' => $form,
        ]);
    }
}
