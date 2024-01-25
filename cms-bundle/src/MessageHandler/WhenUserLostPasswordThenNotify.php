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

namespace Ferienpass\CmsBundle\MessageHandler;

use Ferienpass\CmsBundle\Message\UserLostPassword;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\Recipient\Recipient;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsMessageHandler]
class WhenUserLostPasswordThenNotify
{
    public function __construct(private readonly Notifier $notifier, private readonly UserRepository $repository, private readonly ResetPasswordHelperInterface $resetPasswordHelper)
    {
    }

    public function __invoke(UserLostPassword $message): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['email' => $message->getEmail()]);
        if (null === $user) {
            return;
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return;
        }

        // Store the token object in session for retrieval in check-email route.
        // $this->setTokenObjectInSession($resetToken);

        $notification = $this->notifier->userPassword($resetToken->getToken(), $user);
        if (null === $notification) {
            return;
        }

        $this->notifier->send($notification, new Recipient($user->getEmail()));
    }
}
