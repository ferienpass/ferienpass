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

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Slug\Slug;
use Contao\Dbafs;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Dto\EditHostDto;
use Ferienpass\AdminBundle\Form\EditHostType;
use Ferienpass\AdminBundle\Form\UserInviteType;
use Ferienpass\AdminBundle\Message\HostInvite;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stammdaten')]
final class OrganizationController extends AbstractController
{
    public function __construct(private readonly Connection $connection, private readonly OptIn $optIn, private readonly HostRepository $hostRepository, private readonly Slug $slug, #[Autowire('%contao.upload_path%/logo')] private readonly string $logosDir, #[Autowire('%kernel.project_dir%')] private readonly string $projectDir, private readonly ManagerRegistry $doctrine)
    {
    }

    #[Route('', name: 'admin_profile_index')]
    public function index(Request $request, Breadcrumb $breadcrumb, MessageBusInterface $messageBus): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $organizations = [];

        foreach ($this->hostRepository->findByUser($user) as $host) {
            $form = $this->createForm(UserInviteType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $messageBus->dispatch(new HostInvite($form->get('email')->getData(), $host->getId(), $user->getId()));

                $this->addFlash(...Flash::confirmation()->text(sprintf('Die Einladungs-E-Mail wurde an %s verschickt.', $email))->create());

                return $this->redirectToRoute($request->get('_route'));
            }

            $organizations[] = ['host' => $host, 'members' => $this->fetchMembers($host), 'inviteForm' => $form->createView()];
        }

        return $this->render('@FerienpassAdmin/page/profile/index.html.twig', [
            'organizations' => $organizations,
            'breadcrumb' => $breadcrumb->generate('Stammdaten'),
        ]);
    }

    #[Route('/{id}/bearbeiten', name: 'admin_profile_edit')]
    public function edit(Host $host, Request $request): ?Response
    {
        $this->denyAccessUnlessGranted('edit', $host);

        $form = $this->createForm(EditHostType::class, $hostDto = EditHostDto::fromEntity($host));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $host = $hostDto->toEntity($host);

            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), \PATHINFO_FILENAME);

                $fileExists = fn (string $filename): bool => file_exists(sprintf('%s/%s.%s', $this->logosDir, $filename, (string) $logoFile->guessExtension()));
                $safeFilename = $this->slug->generate($originalFilename, [], $fileExists);
                $newFilename = $safeFilename.'.'.(string) $logoFile->guessExtension();

                try {
                    $logoFile->move($this->logosDir, $newFilename);

                    $relativeFileName = ltrim(str_replace($this->projectDir, '', $this->logosDir), '/').'/'.$newFilename;
                    $fileModel = Dbafs::addResource($relativeFileName);

                    $host->setLogo($fileModel->uuid);
                } catch (FileException) {
                }
            }

            $this->doctrine->getManager()->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->attributes->get('_route'), ['id' => $host->getId()]);
        }

        return $this->render('@FerienpassAdmin/page/profile/edit.html.twig', [
            'host' => $host,
            'form' => $form->createView(),
        ]);
    }

    private function fetchMembers(Host $host): array
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_member', 'm')
            ->innerJoin('m', 'HostMemberAssociation', 'a', 'a.member_id = m.id')
            ->where('a.host_id = :host_id')
            ->setParameter('host_id', $host->getId())
            ->executeQuery()
        ;

        return $statement->fetchAllAssociative();
    }
}
