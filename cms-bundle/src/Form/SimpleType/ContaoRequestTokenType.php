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

namespace Ferienpass\CmsBundle\Form\SimpleType;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContaoRequestTokenType extends AbstractType
{
    public function __construct(private readonly ContaoCsrfTokenManager $tokenManager, #[Autowire(param: 'contao.csrf_token_name')] private readonly string $tokenName)
    {
    }

    public function getParent()
    {
        return HiddenType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['full_name'] = 'REQUEST_TOKEN';
        $view->vars['value'] = $this->tokenManager->getToken($this->tokenName)->getValue();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('mapped', false);
    }
}
