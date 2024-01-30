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

namespace Ferienpass\AdminBundle\Controller\Page;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Export\XlsxExport;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Export\Offer\PrintSheet\PdfExports;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Knp\Menu\FactoryInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/angebote/{edition?null}')]
final class OffersController extends AbstractController
{
    #[Route('{_suffix?}', name: 'admin_offers_index')]
    public function index(?string $_suffix, #[MapEntity(mapping: ['edition' => 'alias'])] ?Edition $edition, OfferRepository $repository, HostRepository $hostRepository, Request $request, Breadcrumb $breadcrumb, FactoryInterface $factory, EditionRepository $editionRepository, XlsxExport $xlsxExport): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('No user');
        }

        $qb = $repository->createQueryBuilder('i');

        if ($request->query->has('host')) {
            $host = $hostRepository->find($request->query->get('host'));
            $this->denyAccessUnlessGranted('view', $host);

            $qb->andWhere(':host MEMBER OF i.hosts')->setParameter('host', $host);
        } elseif (!$this->isGranted('ROLE_ADMIN')) {
            $hosts = $hostRepository->findByUser($user);
            $qb->innerJoin('i.hosts', 'h', Join::WITH, 'h IN (:hosts)')->setParameter('hosts', $hosts);
        }

        if (null !== $edition) {
            $qb->andWhere('i.edition = :edition')->setParameter('edition', $edition->getId(), Types::INTEGER);
        }

        $_suffix = ltrim((string) $_suffix, '.');
        if ('' !== $_suffix) {
            // TODO service-tagged exporter
            if ('xlsx' === $_suffix) {
                return $this->file($xlsxExport->generate($qb), 'angebote.xlsx');
            }
        }

        $items = $qb->getQuery()->getResult();

        $menu = $factory->createItem('offers.editions');

        foreach ($editionRepository->findBy(['archived' => false], ['createdAt' => 'DESC']) as $e) {
            $menu->addChild($e->getName(), [
                'route' => 'admin_offers_index',
                'routeParameters' => ['edition' => $e->getAlias()],
                'current' => null !== $edition && $e->getAlias() === $edition->getAlias(),
            ]);
        }

        return $this->render('@FerienpassAdmin/page/offers/index.html.twig', [
            'qb' => $qb,
            'createUrl' => $this->generateUrl('admin_offers_new', array_filter(['edition' => $edition?->getAlias()])),
            'exports' => ['xlsx'],
            'searchable' => ['name'],
            'edition' => $edition,
            'uncompletedOffers' => (clone $qb)->select('COUNT(i)')->andWhere('i.state = :status')->setParameter('status', Offer::STATE_DRAFT)->getQuery()->getSingleResult() > 0,
            'items' => $items,
            'aside_nav' => $menu,
            'breadcrumb' => $breadcrumb->generate('offers.title', $edition?->getName()),
        ]);
    }

    #[Route('/{id}', name: 'admin_offer_proof', requirements: ['id' => '\d+'])]
    public function show(Offer $offer, Request $request, PdfExports $pdfExports, EntityManagerInterface $em, \Ferienpass\CoreBundle\Session\Flash $flash, MessageBusInterface $messageBus, Breadcrumb $breadcrumb, WorkflowInterface $offerStateMachine): Response
    {
        if ($request->isMethod('delete')) {
            $this->denyAccessUnlessGranted('delete', $offer);

            // Do not delete variants
            if ($offer->isVariantBase() && !$offer->getVariants()->isEmpty()) {
                /** @var Offer $firstVariant */
                $firstVariant = $offer->getVariants()->first();

                $firstVariant->setVariantBase(null);

                /** @var Offer $variant */
                foreach ($offer->getVariants() as $variant) {
                    if ($variant === $firstVariant) {
                        continue;
                    }

                    $variant->setVariantBase($firstVariant);
                }
            }

            $em->remove($offer);
            $em->flush();

            $flash->addConfirmation(text: 'Das Angebot wurde gelöscht.');

            return $this->redirectToRoute('host_offer_list');
        }

        if ($request->isMethod('post') && 'cancel' === $request->get('act')) {
            $this->denyAccessUnlessGranted('cancel', $offer);

            $offerStateMachine->apply($offer, Offer::TRANSITION_CANCEL);

            $flash->addConfirmation(text: 'Das Angebot wurde abgesagt.');

            return $this->redirect($request->getUri());
        }

        if ($request->isMethod('post') && 'relaunch' === $request->get('act')) {
            $this->denyAccessUnlessGranted('relaunch', $offer);

            $offerStateMachine->apply($offer, Offer::TRANSITION_RELAUNCH);

            $flash->addConfirmation(text: 'Das Angebot wurde wiederhergestellt.');

            return $this->redirect($request->getUri());
        }

        $this->denyAccessUnlessGranted('view', $offer);

        return $this->render('@FerienpassAdmin/page/offers/proof.html.twig', [
            'offer' => $offer,
            'hasPdf' => $pdfExports->has(),
            'breadcrumb' => $breadcrumb->generate(['offers.title', ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], [$offer->getEdition()->getName(), ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], $offer->getName()),
        ]);
    }
}
