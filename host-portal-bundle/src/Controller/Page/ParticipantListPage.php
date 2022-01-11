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

use Contao\CoreBundle\ServiceAnnotation\Page;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Export\ParticipantList\PdfExport;
use Ferienpass\HostPortalBundle\Controller\AbstractController;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(path="{id}{_suffix}", type="host_participant_list", defaults={"_suffix": ""}, requirements={"id"="\d+"}, contentComposition=false)
 */
final class ParticipantListPage extends AbstractController
{
    public function __construct(private PdfExport $pdfExport)
    {
    }

    public function __invoke(Offer $offer, string $_suffix, Request $request): Response
    {
        $this->checkToken();

        $this->denyAccessUnlessGranted('participants.view', $offer);

        $_suffix = ltrim($_suffix, '.');
        if ('pdf' === $_suffix) {
            return $this->file($this->pdfExport->generate($offer), 'teilnahmeliste.pdf');
        }

        return $this->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.participant_list', ['offer' => $offer]))
            ->getResponse()
        ;
    }
}
