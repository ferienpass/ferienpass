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
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

// #[ORM\Entity]
// #[ORM\InheritanceType('JOINED')]
// #[ORM\DiscriminatorColumn(name: 'storage', type: 'string')]
// #[ORM\DiscriminatorMap(['offer_media' => OfferMedia::class, 'agreement_letters' => OfferAgreementLetter::class])]
class File
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'pid', referencedColumnName: 'uuid')]
    private ?self $parent = null;

    #[ORM\Column(type: 'integer', length: 10, nullable: true)]
    private ?int $lastModified = null;

    #[ORM\Column(type: 'string', length: 16)]
    private string $type;

    #[ORM\Column(type: 'string', unique: true)]
    private string $path;

    #[ORM\Column(type: 'string', length: 32)]
    private string $hash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'uploaded_by', referencedColumnName: 'id')]
    private ?User $uploadedBy = null;

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): void
    {
        $this->uploadedBy = $uploadedBy;
    }
}
