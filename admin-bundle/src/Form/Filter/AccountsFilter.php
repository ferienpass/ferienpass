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

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountsFilter extends AbstractFilter
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_format' => 'accounts.filter.%name%',
        ]);
    }

    protected function getSorting(): array
    {
        return [
            'createdAt' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.createdAt', 'DESC'),
            'firstname' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.firstname', 'ASC'),
            'lastname' => fn (QueryBuilder $qb) => $qb->addOrderBy('i.lastname', 'ASC'),
        ];
    }
}
