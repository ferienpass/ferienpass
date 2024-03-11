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

namespace Ferienpass\CoreBundle\Dto;

use Ferienpass\CoreBundle\Entity\Offer\BaseOffer;

interface OfferDto
{
    public static function fromEntity(BaseOffer $offer = null): self;

    public function toEntity(BaseOffer $offer = null): BaseOffer;

    public function offerEntity(): ?BaseOffer;
}
