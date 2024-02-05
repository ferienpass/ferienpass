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
use Symfony\Contracts\Translation\TranslatableInterface;

class UserFilter extends AbstractFilterType
{
    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
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

    public function apply(QueryBuilder $qb, FormInterface $form = null): void
    {
        if (null === $form || $form->isEmpty()) {
            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere('i.user = :q_'.$k)
            ->setParameter('q_'.$k, $v)
        ;
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return $form->getData();
    }
}
