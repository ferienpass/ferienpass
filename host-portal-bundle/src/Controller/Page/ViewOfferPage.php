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

namespace Ferienpass\HostPortalBundle\Controller\Page;

use Contao\PageModel;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\HostPortalBundle\Controller\AbstractController;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewOfferPage extends AbstractController
{
    public function __invoke(Offer $offer, Request $request): Response
    {
        $this->checkToken();

        // Access check in fragment

        $pageModel = $request->attributes->get('pageModel');
        if ($pageModel instanceof PageModel) {
            $pageModel->title = $offer->getName();

            if ($date = $offer->getDates()->first()) {
                $pageModel->title .= sprintf(' (%s)', $date->getBegin()->format('d.m.Y'));
            }

            $pageModel->title .= ' - '.implode(', ', $offer->getHosts()->map(fn (Host $h) => $h->getName())->toArray());
        }

        return $this->createPageBuilder($request->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.offer_details', ['offer' => $offer]))
            ->getResponse()
        ;
    }
}
