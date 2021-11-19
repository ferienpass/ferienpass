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
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VacanciesType extends AbstractFilterType
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
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
        $qb
            ->andWhere(
                $qb->expr()
                    ->orX()
                    ->add('o.maxParticipants IS NULL')
                    ->add(sprintf('(%s) < o.maxParticipants',
                        $this->offerRepository->createQueryBuilder('o2')
                            ->select('COUNT(attendances.id)')
                            ->leftJoin('o2.attendances', 'attendances')
                            ->andWhere("attendances.status = 'confirmed'")
                            ->andWhere('o2.id = o.id')
                            ->addGroupBy('o2.id')
                            ->getDQL()))
            )
            ->andWhere('o.cancelled <> 1')
            ->andWhere($qb->expr()
                ->orX()
                ->add('o.requiresApplication <> 1')
                ->add($qb->expr()
                    ->andX()
                    ->add('o.requiresApplication = 1')
                    ->add('o.onlineApplication = 1')
                    ->add($qb->expr()
                        ->orX()
                        ->add('o.applicationDeadline IS NULL')
                        ->add('o.applicationDeadline >= :now')
                    )
                )
            )
        ;
    }
}
