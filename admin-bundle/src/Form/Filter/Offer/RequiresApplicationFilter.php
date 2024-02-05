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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

class RequiresApplicationFilter extends AbstractFilterType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function shallDisplay(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'nein' => false,
                'ja' => true,
            ],
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
            ->andWhere('i.requiresApplication = :q_'.$k)
            ->setParameter('q_'.$k, $v)
        ;
    }

    protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface
    {
        return $form->getData() ? 'mit Anmeldung' : 'ohne Anmeldung';
    }
}
