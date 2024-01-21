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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HostsFilter extends AbstractFilterType
{
    //    public static function getName(): string
    //    {
    //        return 'age';
    //    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'class' => Host::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('h')
                    // ->where("JSON_SEARCH(u.roles, 'one', :role) IS NOT NULL")
                    // ->setParameter('role', 'ROLE_ADMIN')
                    ->orderBy('h.name')
                ;
            },
            'choice_label' => 'name',
            'placeholder' => '-',
        ]);
    }

    public static function apply(QueryBuilder $qb, FormInterface $form): void
    {
        $k = $form->getName();
        $v = $form->getData();

        $qb->innerJoin('i.hosts', 'h', Join::WITH, 'h IN (:q_'.$k.')')->setParameter('q_'.$k, $v);
    }

    //    public function getViewData(FormInterface $form): ?TranslatableInterface
    //    {
    //        return new TranslatableMessage('offerList.filter.age', ['value' => $form->getViewData()]);
    //    }

    //    public function getBlockPrefix(): string
    //    {
    //        return 'filter_age';
    //    }
}
