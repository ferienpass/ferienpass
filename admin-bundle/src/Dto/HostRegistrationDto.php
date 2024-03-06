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

use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;

class HostRegistrationDto
{
    #[Assert\NotBlank()]
    public ?string $firstname = null;

    #[Assert\NotBlank()]
    public ?string $lastname = null;

    #[PhoneNumber(defaultRegion: 'DE')]
    public ?string $userPhone = null;

    #[Assert\Email()]
    public ?string $userEmail = null;
    #[Assert\NotBlank()]
    #[Assert\Length(min: 8)]
    public ?string $userPassword = null;

    #[Assert\NotBlank()]
    public ?string $name = null;
    public ?string $text = null;

    #[Assert\Email]
    public ?string $email = null;

    #[PhoneNumber(defaultRegion: 'DE')]
    public ?string $phone = null;

    #[Assert\Url]
    public ?string $website = null;
    public ?string $street = null;
    public ?string $postal = null;
    public ?string $city = null;

    public function toUser(): User
    {
        $user = new User();

        $user->setFirstname($this->firstname);
        $user->setLastname($this->lastname);
        $user->setPhone($this->userPhone);
        $user->setEmail($this->userEmail);
        $user->setPlainPassword($this->userPassword);

        return $user;
    }

    public function toHost(): Host
    {
        $host = new Host();

        $host->setName($this->name ?? '');
        $host->setText($this->text);
        $host->setPhone($this->phone);
        $host->setEmail($this->email);
        $host->setWebsite($this->website);
        $host->setStreet($this->street);
        $host->setPostal($this->postal);
        $host->setCity($this->city);

        return $host;
    }
}
