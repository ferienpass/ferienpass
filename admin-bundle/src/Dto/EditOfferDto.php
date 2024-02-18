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

use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Dto\OfferDto;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\OfferCategory;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @deprecated
 */
class EditOfferDto implements OfferDto
{
    #[Assert\NotBlank]
    #[Annotation\FormType(required: true)]
    public string $name = '';

    #[Annotation\FormType]
    public ?string $description = null;

    #[Annotation\FormType]
    public ?string $meetingPoint = null;

    #[Annotation\FormType]
    public ?string $bring = null;

    #[Annotation\FormType]
    #[Annotation\EntityType(OfferCategory::class)]
    public Collection $categories;

    #[Annotation\FormType]
    public Collection $dates;

    #[Annotation\FormType]
    public ?\DateTimeInterface $applicationDeadline = null;

    #[Annotation\FormType(placeholder: '-')]
    public ?int $minParticipants = null;

    #[Annotation\FormType(placeholder: 'ohne Begrenzung')]
    public ?int $maxParticipants = null;

    #[Annotation\FormType(placeholder: 'kein Mindestalter')]
    public ?int $minAge = null;

    #[Annotation\FormType(placeholder: 'kein Höchstalter')]
    public ?int $maxAge = null;

    #[Annotation\FormType(showHelp: true)]
    public bool $requiresApplication = false;

    #[Annotation\FormType(showHelp: true)]
    public bool $onlineApplication = false;

    #[Annotation\FormType(showHelp: true)]
    public ?string $applyText = null;

    #[Annotation\FormType(showHelp: true)]
    public ?string $contact = null;

    #[Annotation\FormType]
    public ?int $fee = null;

    #[Annotation\FormType]
    public ?bool $wheelchairAccessible = null;

    public ?string $image = null;

    public function __construct(private readonly ?Offer $offerEntity)
    {
    }

    public static function fromEntity(Offer $offer = null): self
    {
        $self = new self($offer);

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
        $self->minParticipants = $offer->getMinParticipants();
        $self->maxParticipants = $offer->getMaxParticipants();
        $self->minAge = $offer->getMinAge();
        $self->maxAge = $offer->getMaxAge();
        $self->requiresApplication = $offer->requiresApplication();
        $self->onlineApplication = $offer->isOnlineApplication();
        $self->applyText = $offer->getApplyText();
        $self->contact = $offer->getContactUser();
        $self->fee = $offer->getFee();
        $self->image = $offer->getImage();
        $self->wheelchairAccessible = $offer->isWheelchairAccessible();

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
        $offer->setMinParticipants($this->minParticipants);
        $offer->setMaxParticipants($this->maxParticipants);
        $offer->setMinAge($this->minAge);
        $offer->setMaxAge($this->maxAge);
        $offer->setRequiresApplication($this->requiresApplication);
        $offer->setOnlineApplication($this->onlineApplication);
        $offer->setApplyText($this->applyText);
        $offer->setContactUser($this->contact);
        $offer->setFee($this->fee);
        $offer->setImage($this->image);
        $offer->setWheelchairAccessible($this->wheelchairAccessible);

        return $offer;
    }

    public function offerEntity(): ?Offer
    {
        return $this->offerEntity;
    }

    public function addDate(OfferDate $offerDate): void
    {
        $this->dates->add($offerDate->withOffer($this->offerEntity));
    }

    public function removeDate(OfferDate $offerDate): void
    {
        $this->dates->removeElement($offerDate);
    }
}
