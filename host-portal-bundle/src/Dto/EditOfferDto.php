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
use Symfony\Component\Validator\Constraints as Assert;

class EditOfferDto implements OfferDto
{
    /**
     * @Assert\NotBlank()
     */
    #[Annotation\FormType('title')]
    public string $name = '';

    #[Annotation\FormType('title')]
    public ?string $description = null;

    #[Annotation\FormType('title')]
    public ?string $meetingPoint = null;

    #[Annotation\FormType('title')]
    public ?string $bring = null;

    #[Annotation\FormType('title')]
    #[Annotation\EntityType(OfferCategory::class)]
    public Collection $categories;

    #[Annotation\FormType('date')]
    public Collection $dates;

    #[Annotation\FormType('date')]
    public ?\DateTimeInterface $applicationDeadline = null;

    #[Annotation\FormType('date', showHelp: true)]
    public ?string $comment = null;

    #[Annotation\FormType('applications', placeholder: '-')]
    public ?int $minParticipants = null;

    #[Annotation\FormType('applications', placeholder: 'ohne Begrenzung')]
    public ?int $maxParticipants = null;

    #[Annotation\FormType('applications', placeholder: 'kein Mindestalter')]
    public ?int $minAge = null;

    #[Annotation\FormType('applications', placeholder: 'kein Höchstalter')]
    public ?int $maxAge = null;

    #[Annotation\FormType('applications', showHelp: true)]
    public bool $requiresApplication = false;

    #[Annotation\FormType('applications', showHelp: true)]
    public bool $onlineApplication = false;

    #[Annotation\FormType('applications', showHelp: true)]
    public ?string $applyText = null;

    #[Annotation\FormType('applications', showHelp: true)]
    public ?string $contact = null;

    #[Annotation\FormType('applications')]
    public ?int $fee = null;

    //#[Annotation\FormType('applications')]
    //public ?bool $aktivPass = null;

    //#[Annotation\FormType('applications')]
    //public ?array $accessibility = null;

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
        //$self->aktivPass = $offer->isAktivPass();
        //$self->accessibility = $offer->getAccessibility();
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
        //$offer->setAktivPass($this->aktivPass);
        //$offer->setAccessibility($this->accessibility);
        $offer->setImage($this->image);

        return $offer;
    }
}
