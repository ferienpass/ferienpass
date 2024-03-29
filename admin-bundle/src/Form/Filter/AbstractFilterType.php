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

namespace Ferienpass\AdminBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatableInterface;

abstract class AbstractFilterType extends AbstractType
{
    abstract public function apply(QueryBuilder $qb, FormInterface $form): void;

    /**
     * Allows you to modify whether the filter shall be displayed in the form, regardless of whether the filter is applied.
     */
    public function shallDisplay(): bool
    {
        return true;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['isEmpty'] = $form->isEmpty();
        $view->vars['humanReadable'] = $this->getHumanReadableValue($form);
        $view->vars['display'] = $this->shallDisplay();
    }

    abstract protected function getHumanReadableValue(FormInterface $form): null|string|TranslatableInterface;
}
