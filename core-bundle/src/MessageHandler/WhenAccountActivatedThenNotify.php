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

namespace Ferienpass\CoreBundle\MessageHandler;

use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Message\AccountActivated;
use Ferienpass\CoreBundle\Messenger\NotificationHandlerResult;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
class WhenAccountActivatedThenNotify
{
    public function __construct(private readonly Notifier $notifier, private readonly UserRepository $repository)
    {
    }

    public function __invoke(AccountActivated $message): ?NotificationHandlerResult
    {
        /** @var User $user */
        $user = $this->repository->find($message->getUserId());
        if (null === $user) {
            return null;
        }

        $notification = $this->notifier->accountActivated($user);
        if (null === $notification || '' === $email = (string) $user->getEmail()) {
            return null;
        }

        $this->notifier->send($notification, new Recipient($email));

        return null;
    }
}
