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

namespace Ferienpass\AdminBundle\Dto;

use Ferienpass\CoreBundle\Dto\ParticipantDto;
use Ferienpass\CoreBundle\Entity\Participant;

class AddParticipantDto implements ParticipantDto
{
    public ?string $firstname = null;
    public ?string $lastname = null;
    public ?\DateTimeInterface $dateOfBirth = null;
    public ?string $email = null;
    public ?string $mobile = null;
    public ?string $phone = null;

    public static function fromEntity(Participant $participant = null): self
    {
        throw new \BadFunctionCallException();
    }

    public function toEntity(Participant $participant = null): Participant
    {
        $entity = new Participant();

        $entity->setFirstname($this->firstname);
        $entity->setLastname($this->lastname);
        $entity->setDateOfBirth($this->dateOfBirth);
        $entity->setEmail($this->email);
        $entity->setMobile($this->mobile);
        $entity->setPhone($this->phone);

        return $entity;
    }
}
