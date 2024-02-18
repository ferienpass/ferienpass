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

use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Entity\OfferMedia;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class FilesController extends AbstractController
{
    public const STORAGES = [
        'Angebote' => 'offer_media',
        'Logos' => 'logos',
        'Einverständniserklärungen' => 'agreement_letters',
        'Dateiexporte' => 'exports',
        'Contao' => 'files',
    ];

    public function __construct(#[Autowire(service: 'contao.filesystem.mount_manager')] private readonly MountManager $mountManager)
    {
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        foreach (self::STORAGES as $storage) {
            $services["contao.filesystem.virtual.$storage"] = VirtualFilesystemInterface::class;
        }

        return $services;
    }

    #[Route('/dateien/{!storage}/{path?}', name: 'admin_files', requirements: ['path' => '.+'], defaults: ['storage' => 'Angebote'])]
    public function index(string $storage, ?string $path, Breadcrumb $breadcrumb, EntityManagerInterface $em): Response
    {
        $storageName = self::STORAGES[$storage] ?? null;

        /** @noinspection MissingService */
        if (null === $storageName || !$this->container->has("contao.filesystem.virtual.$storageName")) {
            throw $this->createNotFoundException();
        }

        /** @var VirtualFilesystemInterface $filesystem */
        /** @noinspection MissingService */
        $filesystem = $this->container->get("contao.filesystem.virtual.$storageName");
        $contents = $filesystem->listContents((string) $path, accessFlags: VirtualFilesystemInterface::FORCE_SYNC);
        // dd($contents->toArray()[0]);

        if (($mount = $this->mountManager->getMounts()['offer_media']) instanceof LocalFilesystemAdapter) {
            $r = new \ReflectionObject($mount);
            $p = $r->getProperty('rootLocation');
            $p->setAccessible(true);

            $path = $p->getValue($mount);
        }

        $item = $em->getRepository(OfferMedia::class)->find($contents->first()->getUuid());

        return $this->render('@FerienpassAdmin/page/files/index.html.twig', [
            'items' => $contents,
            'current' => $item,
            'absolutePath' => $path ?? '',
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'files.title'),
        ]);
    }
}
