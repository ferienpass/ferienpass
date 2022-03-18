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

namespace Ferienpass\HostPortalBundle\Controller\Fragment;

use Contao\Email;
use Contao\PageModel;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\Dto\HostRegistrationDto;
use Ferienpass\HostPortalBundle\Form\HostRegistrationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class RegistrationController extends AbstractFragmentController
{
    public function __construct(private ManagerRegistry $doctrine, private PasswordHasherInterface $passwordHasher, private string $adminEmail)
    {
    }

    public function __invoke(PageModel $pageModel, Request $request): Response
    {
        $dto = new HostRegistrationDto();
        $form = $this->createForm(HostRegistrationType::class, $dto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();

            $memberModel = $dto->toMemberModel();
            $memberModel->username = $memberModel->email;
            $memberModel->groups = serialize(['1']);
            $memberModel->dateAdded = $memberModel->tstamp = time();
            $memberModel->login = '1';
            $memberModel->disable = '1';

            if (isset($memberModel->plainPassword)) {
                $memberModel->password = $this->passwordHasher->hash($memberModel->plainPassword);
                unset($memberModel->plainPassword);
            }

            try {
                $memberModel->save();
            } catch (\Exception) {
                $this->addFlash(...Flash::error()->headline('Fehler')->text('Ein Fehler ist aufgetreten. Haben Sie bereits in Nutzerkonto?.')->create());

                return $this->redirectToRoute($request->attributes->get('_route'));
            }

            $host = $dto->toHostEntity();
            $host->addMember($memberModel);
            $em->persist($host);
            $em->flush();

            $email = new Email();

            $email->subject = 'Neue Registrierungsanfrage als Veranstalter';

            $email->text = 'Ein neuer Veranstalter hat sich registriert.';
            $email->replyTo($memberModel->email);
            $email->sendTo($pageModel->adminEmail ?? $this->adminEmail);

            $this->addFlash(...Flash::confirmationModal()->headline('Registrierung gesendet')->text('Ihre Registrierung haben wir erhalten. Wir werden sie schnellstmöglich bearbeiten. Sie bekommen von uns eine Mitteilung.')->linkText('Zur Startseite')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        return $this->renderForm('@FerienpassHostPortal/fragment/registration.html.twig', [
            'form' => $form,
        ]);
    }
}
