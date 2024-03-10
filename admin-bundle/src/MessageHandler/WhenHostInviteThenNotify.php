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

namespace Ferienpass\AdminBundle\MessageHandler;

use Ferienpass\AdminBundle\Message\HostInvite;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Notifier\Notifier;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
class WhenHostInviteThenNotify
{
    public function __construct(private readonly Notifier $notifier, private readonly HostRepository $hostRepository, private readonly UserRepository $userRepository, private readonly UrlGeneratorInterface $urlGenerator, private readonly UriSigner $uriSigner)
    {
    }

    public function __invoke(HostInvite $message): void
    {
        /** @var User $user */
        $user = $this->userRepository->find($message->getUserId());
        /** @var Host $host */
        $host = $this->hostRepository->find($message->getHostId());
        if (null === $user || null === $host) {
            return;
        }

        $notification = $this->notifier->userInvitation($user, $host, $message->getEmail());
        if (null === $notification) {
            return;
        }

        $this->notifier->send(
            $notification->actionUrl($this->uriSigner->sign($this->urlGenerator->generate('admin_invitation', ['email' => $message->getEmail(), 'host' => $host->getAlias()], UrlGeneratorInterface::ABSOLUTE_URL))),
            new Recipient($message->getEmail())
        );
    }
}
