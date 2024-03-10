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

namespace Ferienpass\AdminBundle\Components;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Message\HostInvite;
use Ferienpass\AdminBundle\Service\FileUploader;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(route: 'live_component_admin')]
final class OrganizationData extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: ['name', 'phone', 'fax', 'mobile', 'email', 'website', 'postal', 'city', 'street', 'text'])]
    #[Assert\Valid]
    public Host $host;

    #[LiveProp(writable: true)]
    #[Assert\Email]
    public string $inviteeEmail = '';

    #[LiveProp]
    public ?string $isEditing = null;

    #[LiveProp]
    public bool $uploadLogo = false;

    #[LiveProp]
    public bool $addMember = false;

    public function __construct(#[Autowire(service: 'ferienpass.file_uploader.logos')] private readonly FileUploader $fileUploader, private readonly ContaoFramework $contaoFramework)
    {
    }

    #[LiveAction]
    public function edit(#[LiveArg] string $property)
    {
        $this->isEditing = $property;
    }

    #[LiveAction]
    public function showAddMember()
    {
        $this->addMember = true;
    }

    #[LiveAction]
    public function showUploadLogo()
    {
        $this->uploadLogo = true;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, Flash $flash)
    {
        $this->validate();

        $this->isEditing = null;

        $flash->addConfirmation(text: 'Daten gespeichert');

        $entityManager->flush();
    }

    #[LiveAction]
    public function upload(Request $request, EntityManagerInterface $entityManager, Flash $flash)
    {
        $this->contaoFramework->initialize();

        $file = $request->files->get('logo');
        $imgPath = $this->fileUploader->upload($file);

        $fileModel = Dbafs::addResource($imgPath);
        $this->host->setLogo($fileModel->uuid);

        $flash->addConfirmation(text: 'Daten gespeichert');
        $entityManager->flush();

        $this->uploadLogo = false;
    }

    #[LiveAction]
    public function invite(MessageBusInterface $messageBus, Flash $flash)
    {
        $this->validate();

        $user = $this->getUser();
        $messageBus->dispatch(new HostInvite($this->inviteeEmail, $this->host->getId(), $user->getId()));

        $flash->addConfirmation(text: sprintf('Die Einladungs-E-Mail wurde an %s verschickt.', $this->inviteeEmail));

        return $this->redirectToRoute('admin_profile_index');
    }
}
