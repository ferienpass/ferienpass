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
use Ferienpass\AdminBundle\Export\XlsxExport;
use Ferienpass\AdminBundle\Form\EditHostType;
use Ferienpass\AdminBundle\Form\Filter\HostsFilter;
use Ferienpass\AdminBundle\Service\FileUploader;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/veranstaltende')]
final class HostsController extends AbstractController
{
    public function __construct(#[Autowire(service: 'ferienpass.file_uploader.logos')] private readonly FileUploader $fileUploader)
    {
    }

    #[Route('{_suffix?}', name: 'admin_hosts_index', requirements: ['_suffix' => '\.\w+'])]
    public function index(?string $_suffix, HostRepository $repository, Request $request, Breadcrumb $breadcrumb, XlsxExport $xlsxExport): Response
    {
        $qb = $repository->createQueryBuilder('i');

        $_suffix = ltrim((string) $_suffix, '.');
        if ('' !== $_suffix) {
            // TODO service-tagged exporter
            if ('xlsx' === $_suffix) {
                return $this->file($xlsxExport->generate($qb), 'veranstaltende.xlsx');
            }
        }

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassAdmin/page/hosts/index.html.twig', [
            'qb' => $qb,
            'filterType' => HostsFilter::class,
            'exports' => ['xlsx'],
            'searchable' => ['name'],
            'createUrl' => $this->generateUrl('admin_hosts_create'),
            'pagination' => $paginator,
            'breadcrumb' => $breadcrumb->generate('hosts.title'),
        ]);
    }

    #[Route('/neu', name: 'admin_hosts_create')]
    #[Route('/{alias}/bearbeiten', name: 'admin_hosts_edit')]
    public function edit(?Host $host, Request $request, EntityManagerInterface $em, Breadcrumb $breadcrumb, \Ferienpass\CoreBundle\Session\Flash $flash): Response
    {
        $form = $this->createForm(EditHostType::class, $host ?? new Host());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($host = $form->getData())) {
                $em->persist($host);
            }

            $imageFile = $form->get('logo')->getData();
            if ($imageFile) {
                $imageFileName = $this->fileUploader->upload($imageFile);
                $host->setLogo($imageFileName);
            }

            $em->flush();

            $flash->addConfirmation(text: new TranslatableMessage('editConfirm', domain: 'admin'));

            return $this->redirectToRoute('admin_hosts_edit', ['alias' => $host->getAlias()]);
        }

        $breadcrumbTitle = $host ? $host->getName().' (bearbeiten)' : 'hosts.new';

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/hosts/edit.html.twig', [
            'item' => $host,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['hosts.title', ['route' => 'admin_hosts_index']], $breadcrumbTitle),
        ]);
    }
}
