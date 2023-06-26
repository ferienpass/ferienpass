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

#[ORM\Entity(repositoryClass: 'Ferienpass\CoreBundle\Repository\NotificationRepository')]
#[ORM\Table]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $sorting = 0;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $modifiedAt;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $emailSubject;

    #[ORM\Column(type: 'string', nullable: true)]
    private string $emailText;

    #[ORM\Column(type: 'string', nullable: true)]
    private string $smsText;

    public function __construct(string $type)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modifiedAt = new \DateTimeImmutable();

        $this->type = $type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt = null): void
    {
        if (null === $modifiedAt) {
            $modifiedAt = new \DateTimeImmutable();
        }

        $this->modifiedAt = $modifiedAt;
    }

    public function getModifiedAt(): \DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEmailSubject(): string
    {
        return $this->emailSubject;
    }

    public function setEmailSubject(string $emailSubject): void
    {
        $this->emailSubject = $emailSubject;
    }

    public function getEmailText(): string
    {
        return $this->emailText;
    }

    public function setEmailText(string $emailText): void
    {
        $this->emailText = $emailText;
    }

    public function getSmsText(): string
    {
        return $this->smsText;
    }

    public function setSmsText(string $smsText): void
    {
        $this->smsText = $smsText;
    }
}