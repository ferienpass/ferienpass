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

namespace Ferienpass\CoreBundle\Filter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class OfferListFilter
{
    /** @var array<string,mixed> */
    private array $values = [];

    public function __construct(private FormInterface $form, private Session $session)
    {
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function filter(array $values, DoctrineQueryBuilder $qb): self
    {
        // Re-evaluate the form by the actual values from the URL
        $this->form->submit($values);

        foreach ((array) $this->form->getData() as $k => $v) {
            if ($this->form->get($k)->isEmpty()) {
                continue;
            }

            $this->values[$k] = $v;

            switch ($k) {
                case 'name':
                    $qb
                        ->andWhere('o.name LIKE :q_'.$k)
                        ->setParameter('q_'.$k, '%'.addcslashes($v, '%_').'%')
                    ;
                    break;
                case 'favorites':
                    $savedOffers = $this->session->isStarted()
                        ? $this->session->get('saved_offers')
                        : [];

                    $qb
                        ->andWhere('o.id IN (:q_'.$k.')')
                        ->setParameter('q_'.$k, $savedOffers)
                    ;
                    break;
                case 'fee':
                    $qb
                        ->andWhere('o.fee <= :q_'.$k)
                        ->setParameter('q_'.$k, $v)
                    ;
                    break;
                case 'age':
                    $qb
                        ->andWhere($qb->expr()->andX('o.minAge IS NULL OR o.minAge = 0 OR o.minAge <= :q_'.$k, 'o.maxAge IS NULL OR o.maxAge = 0 OR o.maxAge >= :q_'.$k))
                        ->setParameter('q_'.$k, $v)
                    ;
                    break;
                case 'category':
                    $qb->andWhere($qb->expr()->orX(...array_map(fn ($i) => ':q_'.$i.' MEMBER OF o.categories', array_keys($v->toArray()))));
                    foreach ($v as $i => $cat) {
                        $qb->setParameter('q_'.$i, $cat);
                    }

                    break;
                case 'base':
                    $qb
                        ->andWhere($qb->expr()->orX()->add('o.id = :q_'.$k)->add('o.variantBase = :q_'.$k))
                        ->setParameter('q_'.$k, $v)
                    ;

                    break;
                case 'earliest_date':
                    $qb
                        ->andWhere($qb->expr()->orX()->add('dates IS NULL')->add('dates.begin >= :q_'.$k))
                        ->setParameter('q_'.$k, $v)
                    ;
                    break;
                case 'latest_date':
                    \assert($v instanceof \DateTime);
                    // < DATE() +1 day has the same effect as <= DATE() 23:59:59
                    $v->modify('+1 day');
                    $qb
                        ->andWhere($qb->expr()->orX()->add('dates IS NULL')->add('dates.end <= :q_'.$k))
                        ->setParameter('q_'.$k, $v, Types::DATE_MUTABLE)
                    ;
            }
        }

        return $this;
    }
}
