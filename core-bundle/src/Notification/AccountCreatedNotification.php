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

namespace Ferienpass\CoreBundle\Notification;

use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class AccountCreatedNotification extends AbstractNotification implements NotificationInterface, EmailNotificationInterface
{
    use ActionUrlTrait;

    private User $user;

    public static function getName(): string
    {
        return 'account_created';
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

    public function user(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'user' => $this->user,
        ]);
    }

    public static function getAvailableTokens(): array
    {
        return array_merge(parent::getAvailableTokens(), ['user']);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $email = (new NotificationEmail(self::getName()))
            ->to($recipient->getEmail())
            ->replyTo($this->getReplyTo())
            ->subject($this->getSubject())
            ->content($this->getContent())
            ->context($this->getContext())
        ;

        if (null !== $this->actionUrl) {
            $email->action('email.account_created.activate', $this->actionUrl);
        }

        return new EmailMessage($email);
    }
}
