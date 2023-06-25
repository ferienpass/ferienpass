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

namespace Ferienpass\AdminBundle\Controller\Page;

use Contao\Email;
use Contao\MemberModel;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Dto\HostRegistrationDto;
use Ferienpass\AdminBundle\Form\HostRegistrationType;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Ux\Flash;
use NotificationCenter\Model\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/registrierung', name: 'admin_registration')]
final class RegistrationController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private PasswordHasherInterface $passwordHasher, private string $adminEmail, private NormalizerInterface $normalizer, private FormFactoryInterface $formFactory)
    {
    }

    public function __invoke(Request $request): Response
    {
        $dto = new HostRegistrationDto();
        $form = $this->formFactory->create(HostRegistrationType::class, $dto);

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

            $this->notify($host, $memberModel);

            $email = new Email();

            $email->subject = 'Neue Registrierungsanfrage als Veranstalter';

            $email->text = 'Ein neuer Veranstalter hat sich registriert.';
            $email->replyTo($memberModel->email);
            $email->sendTo();

            $this->addFlash(...Flash::confirmationModal()->headline('Registrierung gesendet')->text('Ihre Registrierung haben wir erhalten. Wir werden sie schnellstmöglich bearbeiten. Sie bekommen von uns eine Mitteilung.')->linkText('Zur Startseite')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        return $this->render('@FerienpassAdmin/page/login/registration.html.twig', [
            'form' => $form,
        ]);
    }

    private function notify(Host $host, MemberModel $member): void
    {
        /** @var Notification $notification */
        $notification = Notification::findOneBy('type', 'host_registration');
        if (null === $notification) {
            throw new \LogicException('Notification of type "host_registration" not found');
        }

        $tokens = [];

        $tokens['admin_email'] = $this->adminEmail;

        foreach ($member->row() as $k => $v) {
            $tokens['member_'.$k] = $v;
        }

        foreach ((array) $this->normalizer->normalize($host, null, ['groups' => ['notification']]) as $k => $v) {
            $tokens['host_'.$k] = $v;
        }

        $notification->send($tokens);
    }
}
