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

use Doctrine\ORM\QueryBuilder;
use Ferienpass\AdminBundle\Form\Filter\AbstractFilterType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class OnlineApplicationFilter extends AbstractFilterType
{
    public function getParent(): string
    {
        return CheckboxType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }

    public function apply(QueryBuilder $qb, FormInterface $form): void
    {
        if ($form->isEmpty()) {
            return;
        }

        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere('i.onlineApplication = :q_'.$k)
            ->setParameter('q_'.$k, $v)
        ;
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return $form->getData() ? 'y' : 'n';
    }
}
