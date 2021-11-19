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

namespace Ferienpass\CoreBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\SearchType as BaseSearchType;
use Symfony\Component\Form\FormInterface;

class SearchType extends AbstractFilterType
{
    public function getBlockPrefix()
    {
        return 'search_name';
    }

    public function getParent(): string
    {
        return BaseSearchType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $qb
            ->andWhere('o.name LIKE :q_name')
            ->setParameter('q_name', '%'.addcslashes($form->getData(), '%_').'%')
        ;
    }
}
