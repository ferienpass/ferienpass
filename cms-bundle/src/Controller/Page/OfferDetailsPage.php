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

namespace Ferienpass\CmsBundle\Controller\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\PageModel;
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('offer_details', path: '{alias}', contentComposition: false)]
class OfferDetailsPage extends AbstractController
{
    public function __invoke(string $alias, OfferRepositoryInterface $offerRepository, Request $request): Response
    {
        if (null === $offer = $offerRepository->findByAlias($alias)) {
            throw new PageNotFoundException();
        }

        if (!$request->attributes->getBoolean('preview') && !$offer->isPublished()) {
            throw new PageNotFoundException();
        }

        $edition = $offer->getEdition();
        if (null !== $edition && $edition->getActiveTasks('show_offers')->isEmpty()) {
            throw new PageNotFoundException();
        }

        $this->initializeContaoFramework();

        $pageModel = $request->attributes->get('pageModel');
        if ($pageModel instanceof PageModel) {
            $pageModel->title = $offer->getName();

            if ($date = $offer->getDates()->first()) {
                $pageModel->title .= sprintf(' (%s)', $date->getBegin()->format('d.m.Y'));
            }

            $pageModel->title .= ' - '.implode(', ', $offer->getHosts()->map(fn (Host $h) => $h->getName())->toArray());
        }

        if ($request->query->has('login')) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new InsufficientAuthenticationException();
            }

            return $this->redirectToRoute('offer_details', ['alias' => $offer->getAlias()]);
        }

        return $this->createPageBuilder($pageModel)
            ->addFragment('main', new FragmentReference('ferienpass.fragment.offer_details', ['offer' => $offer]))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.application_form', ['offer' => $offer]))
            ->getResponse()
        ;
    }
}
