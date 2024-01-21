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

namespace Ferienpass\AdminBundle\Form\Filter;

use Ferienpass\AdminBundle\Form\Filter\Offer\CancelledFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\EditionFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\HostsFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\OnlineApplicationFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\PublishedFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\RequiresApplicationFilter;
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersFilter extends AbstractFilter
{
    public static function getEntity(): string
    {
        return Offer::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'offers.filter.%name%',
        ]);
    }

    protected static function getFilters(): array
    {
        return [
            'editions' => EditionFilter::class,
            'hosts' => HostsFilter::class,
            'requires_application' => RequiresApplicationFilter::class,
            'online_application' => OnlineApplicationFilter::class,
            'cancelled' => CancelledFilter::class,
            'published' => PublishedFilter::class,
        ];
    }

    protected static function getSorting(): array
    {
        return [
            'name' => 'name',
            'date' => 'd.begin',
            'host' => 'h.name',
        ];
    }
}
