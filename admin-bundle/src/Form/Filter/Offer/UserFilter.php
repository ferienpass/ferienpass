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

namespace Ferienpass\AdminBundle\Form\Filter\Payment;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Ferienpass\AdminBundle\Form\Filter\AbstractFilterType;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilter extends AbstractFilterType
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
            'class' => User::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->where("JSON_SEARCH(u.roles, 'one', :role) IS NOT NULL")
                    ->setParameter('role', 'ROLE_ADMIN')
                    ->orderBy('u.firstname')
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

        $qb
            ->andWhere('i.user = :q_'.$k)
            ->setParameter('q_'.$k, $v)
        ;
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
