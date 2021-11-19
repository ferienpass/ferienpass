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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FavoritesType extends AbstractFilterType
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('false_values', ['', null]);
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }

    public function modifyQuery(QueryBuilder $qb, FormInterface $form)
    {
        $savedOffers = $this->session->isStarted()
            ? $this->session->get('saved_offers')
            : [];

        $qb
            ->andWhere('o.id IN (:q_favs)')
            ->setParameter('q_favs', $savedOffers)
        ;
    }
}
