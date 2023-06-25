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

use Contao\MemberModel;
use Ferienpass\CoreBundle\Entity\Host;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;

class HostRegistrationDto
{
    /**
     * @Assert\NotBlank
     */
    public ?string $firstname = null;

    /**
     * @Assert\NotBlank
     */
    public ?string $lastname = null;

    /**
     * @PhoneNumber(defaultRegion="DE")
     */
    public ?string $memberPhone = null;

    /**
     * @Assert\Email()
     */
    public ?string $memberEmail = null;
    public ?string $memberPassword = null;

    /**
     * @Assert\NotBlank
     */
    public ?string $name = null;
    public ?string $text = null;

    /**
     * @Assert\Email()
     */
    public ?string $email = null;

    /**
     * @PhoneNumber(defaultRegion="DE")
     */
    public ?string $phone = null;

    /**
     * @Assert\Url()
     */
    public ?string $website = null;
    public ?string $street = null;
    public ?string $postal = null;
    public ?string $city = null;

    public function toMemberModel(): MemberModel
    {
        $memberModel = new MemberModel();

        $memberModel->firstname = $this->firstname ?? '';
        $memberModel->lastname = $this->lastname ?? '';
        $memberModel->phone = $this->memberPhone ?? '';
        $memberModel->email = $this->memberEmail ?? '';
        $memberModel->plainPassword = $this->memberPassword;

        return $memberModel;
    }

    public function toHostEntity(): Host
    {
        $host = new Host();

        $host->setTimestamp(time());

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
