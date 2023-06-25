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

use Contao\FrontendUser;
use Contao\MemberModel;
use Ferienpass\AdminBundle\Form\PersonalDataType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/persoenliche-daten', name: 'admin_user_index')]
final class PersonalDataController extends AbstractController
{
    public function __invoke(Request $request, FormFactoryInterface $formFactory): Response
    {
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response();
        }

        $memberModel = MemberModel::findByPk($user->id);
        $data = $memberModel->row();

        $form = $formFactory->create(PersonalDataType::class, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = array_filter($form->getData());
            foreach ($data as $k => $v) {
                $memberModel->$k = $v;
            }

            $memberModel->save();

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('@FerienpassAdmin/page/user/index.html.twig', [
            'headline' => 'user.title',
            'form' => $form,
        ]);
    }
}
