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

namespace Ferienpass\CoreBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\PageModel;
use Ferienpass\CoreBundle\Form\OfferFiltersType;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

final class OfferListFilterController extends AbstractController
{
    public function __construct(private EditionRepository $editionRepository)
    {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $hasEditions = $this->editionRepository->count([]) > 0;
        $edition = $this->editionRepository->findOneToShow(PageModel::findByPk($request->attributes->get('pageModel')));

        if ($hasEditions && null === $edition) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(OfferFiltersType::class, $request->query->all());
        $form->handleRequest($request);

        // If filters form submitted, redirect to a pretty URL
        if ($form->isSubmitted() && $form->isValid()) {
            $params = [];
            foreach ($form->getViewData() as $key => $value) {
                if (!$form->has($key) || $form->get($key)->isEmpty()) {
                    continue;
                }

                $params[$key] = $value;
            }

            throw new RedirectResponseException($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().'?'.http_build_query($params));
        }

        return $this->renderForm('@FerienpassCore/Fragment/offer_list_filter.html.twig', [
            'form' => $form,
        ]);
    }
}
