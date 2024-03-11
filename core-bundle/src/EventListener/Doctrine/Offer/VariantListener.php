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

namespace Ferienpass\CoreBundle\EventListener\Doctrine\Offer;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Ferienpass\CoreBundle\Entity\Offer\BaseOffer;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;

#[AsDoctrineListener('preUpdate')]
class VariantListener
{
    public static $processing = false;

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof OfferInterface) {
            return;
        }

        if ($entity->isVariant()) {
            return;
        }

        if (self::$processing) {
            return;
        }

        self::$processing = true;

        $entityManager = $args->getObjectManager();

        $base = $entity;

        /** @var BaseOffer $variant */
        foreach ($base->getVariants() as $variant) {
            // TODO these properties should be read from the DTO of the current form
            $variant->setName($base->getName());
            $variant->setDescription($base->getDescription());
            $variant->setMeetingPoint($base->getMeetingPoint());
            $variant->setBring($base->getBring());
            $variant->setMinParticipants($base->getMinParticipants());
            $variant->setMaxParticipants($base->getMaxParticipants());
            $variant->setMinAge($base->getMinAge());
            $variant->setMaxAge($base->getMaxAge());
            $variant->setRequiresApplication($base->requiresApplication());
            $variant->setOnlineApplication($base->isOnlineApplication());
            $variant->setApplyText($base->getApplyText());
            $variant->setContactUser($base->getContactUser());
            $variant->setFee($base->getFee());
            $variant->setImage($base->getImage());

            $entityManager->persist($variant);
        }

        $entityManager->flush();
    }
}
