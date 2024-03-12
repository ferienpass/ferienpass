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

namespace Ferienpass\AdminBundle\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

trait FormSubscriberTrait
{
    private array $subscribers = [];

    /**
     * @return array<EventSubscriberInterface>
     */
    public function getEventSubscribers(): array
    {
        return $this->subscribers;
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }
}
