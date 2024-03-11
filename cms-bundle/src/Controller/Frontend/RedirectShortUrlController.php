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

namespace Ferienpass\CmsBundle\Controller\Frontend;

use Contao\CoreBundle\ContaoCoreBundle;
use Ferienpass\CoreBundle\Entity\Offer\OfferEntityInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{id}', requirements: ['id' => '\d+'], defaults: ['_scope' => ContaoCoreBundle::SCOPE_FRONTEND])]
class RedirectShortUrlController extends AbstractController
{
    public function __invoke(OfferEntityInterface $offer)
    {
        if (!$offer->isPublished()) {
            throw $this->createNotFoundException();
        }

        if ($offer->isVariant() || $offer->hasVariants()) {
            $base = $offer->getVariantBase() ? $offer->getVariantBase()->getId() : $offer->getId();

            return $this->redirectToRoute('offer_list', ['base' => $base], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute('offer_details', ['alias' => $offer->getAlias()], Response::HTTP_MOVED_PERMANENTLY);
    }
}
