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

namespace Ferienpass\AdminBundle\Form\Filter\Offer;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Ferienpass\AdminBundle\Form\Filter\AbstractFilterType;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class HostFilter extends AbstractFilterType
{
    public function __construct(private readonly Security $security, private readonly HostRepository $hostRepository)
    {
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Host::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                $qb = $er->createQueryBuilder('h');

                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    $qb->innerJoin('h.memberAssociations', 'm', Join::WITH, 'm.user = :user')->setParameter('user', $this->security->getUser());
                }

                return $qb->orderBy('h.name');
            },
            'choice_value' => fn (?Host $entity) => $entity?->getAlias(),
            'choice_label' => 'name',
            'placeholder' => '-',
            'multiple' => false,
        ]);
    }

    public function apply(QueryBuilder $qb, FormInterface $form): void
    {
        if ($form->isEmpty() && $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($form->isEmpty() && !$this->security->isGranted('ROLE_ADMIN')) {
            if (!($user = $this->security->getUser()) instanceof User) {
                $qb->where('i.id = 0');

                return;
            }

            $hosts = $this->hostRepository->findByUser($user);
            $qb->innerJoin('i.hosts', 'h', Join::WITH, 'h IN (:hosts)')->setParameter('hosts', $hosts);

            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb->innerJoin('i.hosts', 'h', Join::WITH, 'h IN (:q_'.$k.')')->setParameter('q_'.$k, $v);
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return $form->getData()?->getName();
    }
}
