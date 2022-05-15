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

use Ferienpass\CoreBundle\Dto\HostDto;
use Ferienpass\CoreBundle\Entity\Host;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;

class EditHostDto implements HostDto
{
    /**
     * @Assert\NotBlank
     */
    #[Annotation\FormType('title')]
    public ?string $name = null;

    #[Annotation\FormType('title')]
    public ?string $text = null;

    /**
     * @PhoneNumber(defaultRegion="DE")
     */
    #[Annotation\FormType('contact')]
    public ?string $phone = null;

    /**
     * @Assert\Email()
     */
    #[Annotation\FormType('contact')]
    public ?string $email = null;

    /**
     * @Assert\Url()
     */
    #[Annotation\FormType('contact')]
    public ?string $website = null;

    #[Annotation\FormType('address')]
    public ?string $street = null;

    #[Annotation\FormType('address')]
    public ?string $postal = null;

    #[Annotation\FormType('address')]
    public ?string $city = null;

    public ?string $logo = null;

    public static function fromEntity(Host $host = null): self
    {
        $self = new self();

        if (null === $host) {
            return $self;
        }

        $self->name = $host->getName();
        $self->text = $host->getText();
        $self->phone = $host->getPhone();
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
        $host->setText($this->text);
        $host->setPhone($this->phone);
        $host->setEmail($this->email);
        $host->setWebsite($this->website);
        $host->setStreet($this->street);
        $host->setPostal($this->postal);
        $host->setCity($this->city);
        $host->setLogo($this->logo);

        return $host;
    }
}
