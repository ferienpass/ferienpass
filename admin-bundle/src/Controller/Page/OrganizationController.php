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

use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Knp\Menu\FactoryInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stammdaten')]
final class OrganizationController extends AbstractController
{
    #[Route('/{host?}', name: 'admin_profile_index')]
    public function index(#[MapEntity(mapping: ['host' => 'alias'])] ?Host $host, HostRepository $hostRepository, Breadcrumb $breadcrumb, FactoryInterface $factory): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $hosts = $hostRepository->findByUser($user);
        if (null === $host) {
            $host = $hosts[0] ?? null;
            if (\count($hosts) > 1) {
                return $this->redirectToRoute('admin_profile_index', ['host' => $host->getAlias()]);
            }
        }

        if (null === $host) {
            throw $this->createNotFoundException();
        }

        $menu = $factory->createItem('profile.hosts');
        foreach ($hosts as $h) {
            $menu->addChild($h->getName(), [
                'route' => 'admin_profile_index',
                'routeParameters' => ['host' => $h->getAlias()],
                'current' => $h->getAlias() === $host->getAlias(),
            ]);
        }

        return $this->render('@FerienpassAdmin/page/profile/index.html.twig', [
            'aside_nav' => $menu,
            'host' => $host,
            'breadcrumb' => $breadcrumb->generate('organization.title', $host->getName()),
        ]);
    }
}
