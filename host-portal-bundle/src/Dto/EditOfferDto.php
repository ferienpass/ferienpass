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

namespace Ferienpass\HostPortalBundle\Dto;

use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Dto\OfferDto;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\OfferCategory;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Symfony\Component\Validator\Constraints as Assert;

class EditOfferDto implements OfferDto
{
    /**
     * @Assert\NotBlank()
     */
    public string $name = '';
    public ?string $description = null;
    public ?string $meetingPoint = null;
    public ?string $bring = null;

    /**
     * @psalm-var Collection<int, OfferCategory>
     */
    public Collection $categories;

    /**
     * @psalm-var Collection<int, OfferDate>
     */
    public Collection $dates;
    public ?\DateTimeInterface $applicationDeadline = null;
    public ?string $comment = null;

    public ?int $minParticipants = null;
    public ?int $maxParticipants = null;
    public ?int $minAge = null;
    public ?int $maxAge = null;
    public bool $requiresApplication = false;
    public bool $onlineApplication = false;
    public ?string $applyText = null;
    public ?string $contact = null;
    public ?int $fee = null;
    public ?bool $aktivPass = null;
    public ?array $accessibility = null;

    public ?string $image = null;

    public static function fromEntity(Offer $offer = null): self
    {
        $self = new self();

        if (null === $offer) {
            return $self;
        }

        $self->name = $offer->getName();
        $self->description = $offer->getDescription();
        $self->meetingPoint = $offer->getMeetingPoint();
        $self->bring = $offer->getBring();
        $self->categories = $offer->getCategories();
        $self->dates = $offer->getDates();
        $self->applicationDeadline = $offer->getApplicationDeadline();
        $self->comment = $offer->getComment();
        $self->minParticipants = $offer->getMinParticipants();
        $self->maxParticipants = $offer->getMaxParticipants();
        $self->minAge = $offer->getMinAge();
        $self->maxAge = $offer->getMaxAge();
        $self->requiresApplication = $offer->requiresApplication();
        $self->onlineApplication = $offer->isOnlineApplication();
        $self->applyText = $offer->getApplyText();
        $self->contact = $offer->getContact();
        $self->fee = $offer->getFee();
        $self->aktivPass = $offer->isAktivPass();
        $self->accessibility = $offer->getAccessibility();
        $self->image = $offer->getImage();

        return $self;
    }

    public function toEntity(Offer $offer = null): Offer
    {
        $offer = $offer ?? new Offer();

        $offer->setName($this->name);
        $offer->setDescription($this->description);
        $offer->setMeetingPoint($this->meetingPoint);
        $offer->setBring($this->bring);
        $offer->setCategories($this->categories);
        $offer->setDates($this->dates);
        $offer->setApplicationDeadline($this->applicationDeadline);
        $offer->setComment($this->comment);
        $offer->setMinParticipants($this->minParticipants);
        $offer->setMaxParticipants($this->maxParticipants);
        $offer->setMinAge($this->minAge);
        $offer->setMaxAge($this->maxAge);
        $offer->setRequiresApplication($this->requiresApplication);
        $offer->setOnlineApplication($this->onlineApplication);
        $offer->setApplyText($this->applyText);
        $offer->setContact($this->contact);
        $offer->setFee($this->fee);
        $offer->setAktivPass($this->aktivPass);
        $offer->setAccessibility($this->accessibility);
        $offer->setImage($this->image);

        return $offer;
    }
}
