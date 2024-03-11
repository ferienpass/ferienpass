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

namespace Ferienpass\CoreBundle\Repository;

use Ferienpass\CoreBundle\Entity\Offer\OfferEntityInterface;

interface OfferRepositoryInterface extends RepositoryInterface
{
    public function findByAlias(string $alias): ?OfferEntityInterface;

    public function createCopy(OfferEntityInterface $original): OfferEntityInterface;

    public function createVariant(OfferEntityInterface $original): OfferEntityInterface;
}
