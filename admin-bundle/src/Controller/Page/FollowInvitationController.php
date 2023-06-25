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

use Contao\CoreBundle\OptIn\OptIn;
use Contao\FrontendUser;
use Contao\MemberModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ferienpass\AdminBundle\Form\AcceptInvitationType;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/einladung', name: 'admin_invitation')]
final class FollowInvitationController extends AbstractController
{
    public function __construct(private Connection $connection, private PasswordHasherInterface $passwordHasher, private OptIn $optIn, private HostRepository $hostRepository)
    {
    }

    public function __invoke(Request $request)
    {
        // Find an unconfirmed token
        if ((!$optInToken = $this->optIn->find((string) $request->query->get('token')))
            || !$optInToken->isValid()
            || \count($relatedRecords = $optInToken->getRelatedRecords()) < 1
            || !\array_key_exists('Host', $relatedRecords)
            || !\array_key_exists('tl_member', $relatedRecords)) {
            $error = 'MSC.invalidToken';

            return $this->render('@FerienpassAdmin/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        if ($optInToken->isConfirmed()) {
            $error = 'MSC.tokenConfirmed';

            return $this->render('@FerienpassAdmin/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        $user = $this->container->get('contao.framework')->createInstance(FrontendUser::class);

        $memberModel = new MemberModel();
        if ($user->id) {
            $form = $this->createForm(AcceptInvitationType::class, $user);
        } else {
            $form = $this->createForm(AcceptInvitationType::class, $memberModel);
        }

        $hostId = reset($relatedRecords['Host']);
        $inviter = reset($relatedRecords['tl_member']);

        $host = $this->hostRepository->find($hostId);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->id) {
                $memberModel->password = $this->passwordHasher->hash($memberModel->password ?? '');
                $this->createNewUser($memberModel);
                $this->addHost((int) $memberModel->id, (int) $hostId);

                $this->addFlash(...Flash::confirmation()->text('Account erstellt. Bitte melden Sie sich nun mit Ihrer E-Mail-Adresse an.')->create());

                $optInToken->confirm();

                return $this->redirect('/');
            }

            $this->addHost((int) $user->id, (int) $hostId);

            $optInToken->confirm();

            $this->addFlash(...Flash::confirmation()->text('Einladung angenommen')->create());

            return $this->redirect('/');
        }

        return $this->renderForm('@FerienpassAdmin/fragment/follow_invitation.html.twig', [
            'member' => MemberModel::findByPk($inviter),
            'host' => $host,
            'form' => $form,
            'invitee_email' => $optInToken->getEmail(),
        ]);
    }

    private function createNewUser(MemberModel $user): void
    {
        $user->username = $user->email;
        $user->tstamp = $user->dateAdded = time();
        $user->login = true;
        $user->groups = serialize(['1']);

        $user->save();

        // $this->dispatchMessage(new AccountCreated((int) $user->id));
    }

    private function addHost(int $userId, int $hostId): void
    {
        try {
            $this->connection->insert('HostMemberAssociation', ['member_id' => $userId, 'host_id' => $hostId]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        // $this->dispatchMessage(new AccountCreated((int) $user->id));
    }
}
