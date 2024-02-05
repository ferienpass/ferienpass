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
use Ferienpass\CoreBundle\Entity\Edition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class EditionFilter extends AbstractFilterType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Edition::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                $qb = $er->createQueryBuilder('e');

                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    $qb->where('e.archived <> 1');
                }

                return $qb->orderBy('e.name');
            },
            'choice_value' => fn (?Edition $entity) => $entity?->getAlias(),
            'choice_label' => 'name',
            'placeholder' => '-',
            'multiple' => false,
        ]);
    }

    public function apply(QueryBuilder $qb, FormInterface $form): void
    {
        if ($form->isEmpty()) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $qb->innerJoin('i.edition', 'e', Join::WITH, 'e IN (SELECT edition FROM '.Edition::class.' edition WHERE edition.archived <> 1)');
            }

            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb->andWhere('i.edition = :q_'.$k)->setParameter('q_'.$k, $v);
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return $form->getData()?->getName();
    }
}
