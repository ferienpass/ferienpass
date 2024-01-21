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

use Ferienpass\AdminBundle\Form\Filter\Offer\HostsFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\OnlineApplicationFilter;
use Ferienpass\CoreBundle\Entity\Payment;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentsFilter extends AbstractFilter
{
    public static function getEntity(): string
    {
        return Payment::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'payments.filter.%name%',
        ]);
    }

    protected static function getFilters(): array
    {
        return [
            'status' => OnlineApplicationFilter::class,
            'user' => HostsFilter::class,
        ];
    }

    protected static function getSorting(): array
    {
        return [
            'createdAt' => ['createdAt', 'DESC'],
            'amount' => ['totalAmount', 'DESC'],
        ];
    }
}
