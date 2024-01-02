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

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditHostType;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/veranstaltende')]
final class HostsController extends AbstractController
{
    #[Route('', name: 'admin_hosts_index')]
    public function index(HostRepository $repository, Request $request, Breadcrumb $breadcrumb): Response
    {
        $qb = $repository->createQueryBuilder('e');
        $qb->orderBy('e.name');

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassAdmin/page/hosts/index.html.twig', [
            'createUrl' => $this->generateUrl('admin_hosts_create'),
            'pagination' => $paginator,
            'breadcrumb' => $breadcrumb->generate('hosts.title'),
        ]);
    }

    #[Route('/neu', name: 'admin_hosts_create')]
    #[Route('/{alias}/bearbeiten', name: 'admin_hosts_edit')]
    public function edit(?Host $host, Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, Breadcrumb $breadcrumb, \Ferienpass\CoreBundle\Session\Flash $flash): Response
    {
        $form = $formFactory->create(EditHostType::class, $host ?? new Host());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($host = $form->getData())) {
                $em->persist($host);
            }

            $em->flush();

            $flash->addConfirmation(text: new TranslatableMessage('editConfirm', domain: 'admin'));

            return $this->redirectToRoute('admin_hosts_edit', ['alias' => $host->getAlias()]);
        }

        $breadcrumbTitle = $host ? $host->getName().' (bearbeiten)' : 'hosts.new';

        return $this->render('@FerienpassAdmin/page/hosts/edit.html.twig', [
            'item' => $host,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['hosts.title', ['route' => 'admin_hosts_index']], $breadcrumbTitle),
        ]);
    }

    #[Route('/{alias}', name: 'admin_hosts_show')]
    public function show(Host $host, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/hosts/show.html.twig', [
            'host' => $host,
            'breadcrumb' => $breadcrumb->generate(['hosts.title', ['route' => 'admin_hosts_index']], $host->getName()),
        ]);
    }
}
