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

namespace Ferienpass\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ferienpass\CoreBundle\Repository\NotificationLogRepository;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;

#[ORM\Entity(repositoryClass: NotificationLogRepository::class)]
class NotificationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MessageLog::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'log_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private MessageLog $logEntry;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    // #[ORM\Column(name: 'message', type: 'json_document')]
    private object $message;

    #[ORM\Column(name: 'recipients', type: 'json')]
    private array $recipients;

    #[ORM\Column(name: 'sender', type: 'string')]
    private string $sender;

    public function __construct(MessageLog $logEntry, Message $message, Address $sender, Address ...$recipients)
    {
        $this->logEntry = $logEntry;
        $this->message = $message;
        $this->sender = $sender->toString();
        $this->recipients = array_map(fn (Address $a) => $a->toString(), $recipients);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLogEntry(): MessageLog
    {
        return $this->logEntry;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSender(): string
    {
        return $this->sender;
    }
}
