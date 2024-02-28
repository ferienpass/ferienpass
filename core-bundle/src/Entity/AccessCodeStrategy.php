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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class AccessCodeStrategy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255, nullable: false, options: ['default' => ''])]
    private string $name = '';

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'strategy', targetEntity: AccessCode::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $codes;

    #[ORM\Column(type: 'boolean')]
    private bool $unique = true;
    #[ORM\Column(type: 'integer')]
    private int $max = 1;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getCodes(): Collection
    {
        return $this->codes;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    public function isEnabledParticipant(Participant $participant): bool
    {
        return $this->codes->filter(fn (AccessCode $code) => $code->getParticipants()->contains($participant))->count() > 0;
    }
}
