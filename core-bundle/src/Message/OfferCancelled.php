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

namespace Ferienpass\CoreBundle\Message;

use Ferienpass\CoreBundle\Entity\Offer\OfferEntityInterface;

/**
 * This message is dispatched, when an offer has been cancelled.
 */
class OfferCancelled implements LoggableMessageInterface
{
    public function __construct(private readonly int $offerId)
    {
    }

    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function getRelated(): array
    {
        return [
            OfferEntityInterface::class => $this->offerId,
        ];
    }
}
