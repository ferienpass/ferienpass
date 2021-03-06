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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Ferienpass\CoreBundle\Repository\HostRepository")
 */
class Host
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(name="tstamp", type="integer", options={"unsigned"=true})
     */
    private int $timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default"=""})
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private ?string $alias = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $phone;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $fax;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $mobile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $website;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private ?string $postal;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $street;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $text;

    /**
     * @ORM\Column(type="binary_string", length=16, nullable=true)
     */
    private ?string $logo = null;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private ?string $active = null;

    /**
     * @ORM\OneToMany(targetEntity="Ferienpass\CoreBundle\Entity\HostMemberAssociation", mappedBy="host")
     */
    private Collection $memberAssociations;

    /**
     * @ORM\ManyToMany(targetEntity="Ferienpass\CoreBundle\Entity\Offer", mappedBy="hosts")
     */
    private Collection $offers;

    public function addMemberAssociation(HostMemberAssociation $memberAssociation): self
    {
        $this->memberAssociations[] = $memberAssociation;

        return $this;
    }

    public function removeMemberAssociation(HostMemberAssociation $memberAssociation): void
    {
        $this->memberAssociations->removeElement($memberAssociation);
    }

    public function getMemberAssociations(): Collection
    {
        return $this->memberAssociations;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getPostal(): ?string
    {
        return $this->postal;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function getActive(): ?string
    {
        return $this->active;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function setFax(?string $fax): void
    {
        $this->fax = $fax;
    }

    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function setPostal(?string $postal): void
    {
        $this->postal = $postal;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function setActive(?string $active): void
    {
        $this->active = $active;
    }

    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): void
    {
        $this->offers->add($offer);
    }
}
