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
use Doctrine\DBAL\Connection;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\Form\UserInviteType;
use NotificationCenter\Model\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

final class OrganizationController extends AbstractFragmentController
{
    public function __construct(private Security $security, private Connection $connection, private OptIn $optIn, private HostRepository $hostRepository)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $organizations = [];

        foreach ($this->hostRepository->findByMemberId((int) $user->id) as $host) {
            $form = $this->createForm(UserInviteType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->invite($email = $form->getData()['email'], $host, $user);

                return $this->redirectToRoute($request->get('_route'));
            }

            $organizations[] = ['host' => $host, 'members' => $this->fetchMembers($host), 'inviteForm' => $form->createView()];
        }

        return $this->render('@FerienpassHostPortal/fragment/organization_reader.html.twig', [
            'organizations' => $organizations,
        ]);
    }

    private function invite(string $email, Host $host, FrontendUser $user): void
    {
        /** @var Notification $notification */
        $notification = Notification::findOneBy('type', 'host_invite_member');
        if (null === $notification) {
            throw new \LogicException('Notification of type host_invite_member not found');
        }

        $tokens = [];

        $optInToken = $this->optIn->create('invite', $email, ['Host' => [$host->getId()], 'tl_member' => [$user->id]]);

        $tokens['invitee_email'] = $email;
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];
        $tokens['link'] = $this->generateUrl('host_follow_invitation',
            ['token' => $optInToken->getIdentifier()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        foreach ($user->getData() as $k => $v) {
            $tokens['member_'.$k] = $v;
        }

        $tokens['host_name'] = $host->getName();

        $notification->send($tokens);

        $this->addFlash(...Flash::confirmation()->text(sprintf('Die Einladungs-E-Mail wurde an %s verschickt.', $email))->create());
    }

    private function fetchMembers(Host $host): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_member', 'm')
            ->innerJoin('m', 'HostMemberAssociation', 'a', 'a.member_id = m.id')
            ->where('a.host_id = :host_id')
            ->setParameter('host_id', $host->getId())
            ->executeQuery()
        ;

        return $statement->fetchAllAssociative();
    }
}
