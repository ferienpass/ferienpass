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

namespace Ferienpass\AdminBundle\Controller\Fragment;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Ferienpass\CoreBundle\Form\ListFiltersType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This controller displays and handles the filter forms, and redirects to the filtered URL, i.e., ?filter=value.
 * The actual filtering is handled by the corresponding list controller.
 */
final class ListFilterController extends AbstractController
{
    public function __invoke(Request $request, Session $session, FormFactoryInterface $formFactory): Response
    {
        // Get the normalized form data from query
        if ($request->query->count()) {
            $form = $this->buildForm($formFactory);
            $form->submit($request->query->all());
            $data = $form->getData();
        }

        // Build the short form
        $shortForm = $this->buildForm($formFactory, $data ?? null, short: true);

        // If filters form submitted, redirect to a pretty URL
        $shortForm->handleRequest($request);
        if ($shortForm->isSubmitted() && $shortForm->isValid()) {
            throw new RedirectResponseException($this->getFilterUrl($shortForm, $request));
        }

        // Build the full form
        $form = $this->buildForm($formFactory, $data ?? null);
        $form->handleRequest($request);

        // If filters form submitted, redirect to a pretty URL
        if ($form->isSubmitted() && $form->isValid()) {
            throw new RedirectResponseException($this->getFilterUrl($form, $request));
        }

        return $this->renderForm('@FerienpassAdmin/components/_list_filter.html.twig', [
            'shortForm' => $shortForm,
            'fullForm' => $form,
        ]);
    }

    private function buildForm(FormFactoryInterface $formFactory, array $data = null, bool $short = false)
    {
        $options = [];
        if ($short) {
            $options['short'] = true;
        }

        return $formFactory->create(ListFiltersType::class, $data, $options);
    }

    private function getFilterUrl(FormInterface $form, Request $request): string
    {
        $params = HeaderUtils::parseQuery((string) $request->getQueryString());

        foreach (array_keys((array) $form->getViewData()) as $attr) {
            if (!$form->has($attr) || $form->get($attr)->isEmpty()) {
                unset($params[$attr]);
                continue;
            }

            $value = $form->get($attr)->getViewData();
            $params[$attr] = $value;
        }

        // New filter settings are not compatible with pagination
        unset($params['page']);

        $qs = \count($params) ? '?'.http_build_query($params) : '';

        return $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs;
    }
}
