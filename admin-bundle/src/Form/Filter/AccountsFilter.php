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

use Ferienpass\CoreBundle\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountsFilter extends AbstractFilter
{
    public static function getEntity(): string
    {
        return User::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'accounts.filter.%name%',
        ]);
    }

    protected static function getFilters(): array
    {
        return [
            // disable
        ];
    }

    protected static function getSorting(): array
    {
        return [
            'createdAt' => ['i.createdAt', 'DESC'],
            'firstname' => ['i.firstname', 'ASC'],
            'lastname' => ['i.lastname', 'ASC'],
        ];
    }
}
