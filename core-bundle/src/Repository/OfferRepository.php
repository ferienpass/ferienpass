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

class OfferRepository extends EntityRepository implements OfferRepositoryInterface
{
    public function findByAlias(string $alias): ?OfferEntityInterface
    {
        return $this->findOneBy(['alias' => $alias]);
    }

    public function createCopy(OfferEntityInterface $original): OfferEntityInterface
    {
        $new = $this->createNew();

        $new->setName($original->getName().' (Kopie)');
        $new->setDescription($original->getDescription());
        $new->setMeetingPoint($original->getMeetingPoint());
        $new->setBring($original->getBring());
        $new->setMinParticipants($original->getMinParticipants());
        $new->setMaxParticipants($original->getMaxParticipants());
        $new->setMinAge($original->getMinAge());
        $new->setMaxAge($original->getMaxAge());
        $new->setRequiresApplication($original->requiresApplication());
        $new->setOnlineApplication($original->isOnlineApplication());
        $new->setApplyText($original->getApplyText());
        $new->setContactUser($original->getContactUser());
        $new->setFee($original->getFee());
        $new->setImage($original->getImage());
        foreach ($original->getHosts() as $host) {
            $new->addHost($host);
        }

        return $new;
    }

    public function createVariant(OfferEntityInterface $original): OfferEntityInterface
    {
        $new = $this->createCopy($original);
        $new->setVariantBase($original);

        return $new;
    }
}
