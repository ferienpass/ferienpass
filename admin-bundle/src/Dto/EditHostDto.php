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

use Ferienpass\CoreBundle\Dto\HostDto;
use Ferienpass\CoreBundle\Entity\Host;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @deprecated
 */
class EditHostDto implements HostDto
{
    #[Assert\NotBlank]
    public ?string $name = null;
    public ?string $alias = null;
    public ?string $text = null;
    #[PhoneNumber(defaultRegion: 'DE')]
    public ?string $phone = null;
    #[PhoneNumber(defaultRegion: 'DE')]
    public ?string $fax = null;
    #[PhoneNumber(defaultRegion: 'DE')]
    public ?string $mobile = null;
    #[Assert\Email]
    public ?string $email = null;
    #[Assert\Url]
    public ?string $website = null;
    public ?string $street = null;
    public ?string $postal = null;
    public ?string $city = null;
    public ?string $logo = null;

    public static function fromEntity(Host $host = null): self
    {
        $self = new self();

        if (null === $host) {
            return $self;
        }

        $self->name = $host->getName();
        $self->alias = $host->getAlias();
        $self->text = $host->getText();
        $self->phone = $host->getPhone();
        $self->fax = $host->getFax();
        $self->mobile = $host->getMobile();
        $self->email = $host->getEmail();
        $self->website = $host->getWebsite();
        $self->street = $host->getStreet();
        $self->postal = $host->getPostal();
        $self->city = $host->getCity();
        $self->logo = $host->getLogo();

        return $self;
    }

    public function toEntity(Host $host = null): Host
    {
        $host ??= new Host();

        $host->setName($this->name);
        $host->setAlias($this->alias);
        $host->setText($this->text);
        $host->setPhone($this->phone);
        $host->setFax($this->fax);
        $host->setMobile($this->mobile);
        $host->setEmail($this->email);
        $host->setWebsite($this->website);
        $host->setStreet($this->street);
        $host->setPostal($this->postal);
        $host->setCity($this->city);
        $host->setLogo($this->logo);

        return $host;
    }
}
