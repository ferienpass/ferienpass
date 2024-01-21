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

class OnlineApplicationFilter extends AbstractFilterType
{
    //    public static function getName(): string
    //    {
    //        return 'age';
    //    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //        parent::configureOptions($resolver);

        $resolver->setDefaults([
        ]);
    }

    public static function apply(QueryBuilder $qb, FormInterface $form): void
    {
        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere('i.onlineApplication = :q_'.$k)
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
