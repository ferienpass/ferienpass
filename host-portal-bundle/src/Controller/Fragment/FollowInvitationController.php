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

use Contao\CoreBundle\OptIn\OptIn;
use Contao\FrontendUser;
use Contao\MemberModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\HostPortalBundle\Form\AcceptInvitationType;
use Symfony\Component\HttpFoundation\Request;

final class FollowInvitationController extends AbstractFragmentController
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(Request $request)
    {
        /** @var OptIn $optIn */
        $optIn = $this->get('contao.opt-in');

        // Find an unconfirmed token
        if ((!$optInToken = $optIn->find((string) $request->query->get('token')))
            || !$optInToken->isValid()
            || \count($relatedRecords = $optInToken->getRelatedRecords()) < 1
            || !\array_key_exists('Host', $relatedRecords)
            || !\array_key_exists('tl_member', $relatedRecords)) {
            $error = 'MSC.invalidToken';

            return $this->render('@FerienpassHostPortal/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        if ($optInToken->isConfirmed()) {
            $error = 'MSC.tokenConfirmed';

            return $this->render('@FerienpassHostPortal/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        $user = $this->get('contao.framework')->createInstance(FrontendUser::class);

        $memberModel = new MemberModel();
        if ($user->id) {
            $form = $this->createForm(AcceptInvitationType::class, $user);
        } else {
            $form = $this->createForm(AcceptInvitationType::class, $memberModel);
        }

        $hostId = reset($relatedRecords['Host']);
        $inviter = reset($relatedRecords['tl_member']);

        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->id) {
                $encoder = $this->get('security.encoder_factory')->getEncoder(FrontendUser::class);

                $memberModel->password = $encoder->encodePassword($memberModel->password, null);
                $this->createNewUser($memberModel);
                $this->addHost((int) $memberModel->id, (int) $hostId);

                $this->addFlash('confirmation', 'Account erstellt. Bitte melden Sie sich nun mit Ihrer E-Mail-Adresse an.');

                $optInToken->confirm();

                return $this->redirect('/');
            }

            $this->addHost((int) $user->id, (int) $hostId);

            $optInToken->confirm();

            $this->addFlash('confirmation', 'Einladung angenommen');

            return $this->redirect('/');
        }

        return $this->render('@FerienpassHostPortal/fragment/follow_invitation.html.twig', [
            'member' => MemberModel::findByPk($inviter),
            'host' => $host,
            'form' => $form->createView(),
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

        //$this->dispatchMessage(new AccountCreated((int) $user->id));
    }

    private function addHost(int $userId, int $hostId): void
    {
        try {
            $this->connection->insert('HostMemberAssociation', ['member_id' => $userId, 'host_id' => $hostId]);
        } catch (UniqueConstraintViolationException $e) {
            return;
        }

        //$this->dispatchMessage(new AccountCreated((int) $user->id));
    }
}
