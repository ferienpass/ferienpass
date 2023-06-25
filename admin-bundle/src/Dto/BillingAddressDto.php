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
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Symfony\Component\Validator\Constraints as Assert;

class BillingAddressDto
{
    #[Assert\NotBlank]
    public ?string $address = null;

    #[Assert\Email]
    public ?string $email = null;

    public array $items;

    public static function fromMemberModel(MemberModel $memberModel)
    {
        $self = new self();

        $self->address = <<<EOF
$memberModel->firstname $memberModel->lastname
$memberModel->street
$memberModel->postal $memberModel->city
EOF;

        $self->email = $memberModel->email;

        $self->address = trim($self->address);

        return $self;
    }

    public static function fromParticipant(array $items, ?Participant $participant)
    {
        if (null === $participant?->getMember()) {
            $self = new self();
        } else {
            $self = self::fromMemberModel($participant->getMember());
        }

        $self->items = $items;

        if ($participant?->getEmail()) {
            $self->email = $participant->getEmail();
        }

        return $self;
    }

    public function toPayment(Payment $payment): Payment
    {
        $payment->setBillingAddress($this->address);
        $payment->setBillingEmail($this->email);

        return $payment;
    }
}
