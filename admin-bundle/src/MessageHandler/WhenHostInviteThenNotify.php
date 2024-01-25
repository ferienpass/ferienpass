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
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
class WhenHostInviteThenNotify
{
    public function __construct(private readonly Notifier $notifier, private readonly HostRepository $hostRepository, private readonly UserRepository $userRepository)
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

        $this->notifier->send($notification, new Recipient($message->getEmail()));
    }
}
