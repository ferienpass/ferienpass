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
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class StatusFilter extends AbstractFilterType
{
    //    public static function getName(): string
    //    {
    //        return 'age';
    //    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'choices' => [Offer::STATE_DRAFT, Offer::STATE_COMPLETED, Offer::STATE_REVIEWED, Offer::STATE_PUBLISHED],
            'choice_label' => function (string $choice): TranslatableMessage {
                return new TranslatableMessage('offers.status.'.$choice, [], 'admin');
            },
            'placeholder' => '-',
            'expanded' => false,
            'multiple' => false,
        ]);
    }

    public static function apply(QueryBuilder $qb, FormInterface $form): void
    {
        $k = $form->getName();
        $v = $form->getData();

        $qb
            ->andWhere('i.state = :q_'.$k)
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
