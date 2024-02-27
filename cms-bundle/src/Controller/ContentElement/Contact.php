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

namespace Ferienpass\CmsBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(type: 'contact', category: 'texts')]
class Contact extends AbstractContentElementController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');

            $template->title = $this->container->get('translator')->trans('CTE.'.$this->getType().'.0', [], 'contao_modules');
            $template->wildcard = $this->container->get('translator')->trans('CTE.'.$this->getType().'.1', [], 'contao_modules');

            return new Response($template->parse());
        }

        $headline = StringUtil::deserialize($model->headline, true);

        return $this->render('@FerienpassCms/fragment/contact.html.twig', [
            'text' => $model->text,
            'address' => $model->address,
            'phone' => $model->phone,
            'email' => $model->email,
            'headline' => \is_array($headline) ? $headline['value'] : $headline,
            'form' => Controller::getForm($model->form),
        ]);
    }
}
