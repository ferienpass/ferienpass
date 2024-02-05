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

namespace Ferienpass\CmsBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Form\UserPersonalDataType;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PersonalDataController extends AbstractFragmentController
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(UserPersonalDataType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());
        }

        return $this->render('@FerienpassCms/fragment/user_account/personal_data.html.twig', ['form' => $form]);
    }
}
