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

use Ferienpass\AdminBundle\Form\Filter\Payment\StatusFilter;
use Ferienpass\AdminBundle\Form\Filter\Payment\UserFilter;
use Ferienpass\CoreBundle\Entity\Payment;

class PaymentsFilter extends AbstractFilter
{
    public static function getEntity(): string
    {
        return Payment::class;
    }

    protected static function getFilters(): array
    {
        return [
            'status' => StatusFilter::class,
            'user' => UserFilter::class,
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
