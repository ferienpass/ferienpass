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

namespace Ferienpass\AdminBundle\Components\Settings;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Form\EditOfferCategoriesType;
use Ferienpass\CoreBundle\Entity\OfferCategory;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(template: '@FerienpassAdmin/components/EditForm.html.twig', route: 'live_component_admin')]
class OfferCategories extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public $useLiveActionSubmit = true;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, Flash $flash)
    {
        $this->submitForm();

        /** @var OfferCategory[] $categories */
        $categories = $this->getForm()->get('categories')->getData();

        foreach ($categories as $category) {
            if (!$entityManager->contains($category)) {
                $entityManager->persist($category);
            }
        }
        $entityManager->flush();

        $flash->addConfirmation(text: 'Die Daten wurden gespeichert.');

        return $this->redirectToRoute('admin_tools_settings');
    }

    protected function instantiateForm(): FormInterface
    {
        $categories = $this->entityManager->getRepository(OfferCategory::class)->findAll();

        return $this->createForm(EditOfferCategoriesType::class, ['categories' => $categories]);
    }
}
