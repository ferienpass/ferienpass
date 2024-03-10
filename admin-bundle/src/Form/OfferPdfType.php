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

namespace Ferienpass\AdminBundle\Form;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OfferPdfType extends AbstractType
{
    public function __construct(private readonly ContaoFramework $contaoFramework)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        if (!($uuid = $form->getParent()->getData()->getAgreementLetter())) {
            return;
        }

        $this->contaoFramework->initialize();

        $fileModel = FilesModel::findByPk($uuid);
        if (null === $fileModel) {
            return;
        }

        $view->vars['filename'] = $fileModel->name;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('mapped', false);
        $resolver->setDefault('label', false);
    }
}
