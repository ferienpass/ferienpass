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

namespace Ferienpass\HostPortalBundle\Controller\Fragment;

use Contao\FrontendUser;
use Contao\MemberModel;
use Ferienpass\HostPortalBundle\Form\PersonalDataType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

final class PersonalDataController extends AbstractFragmentController
{
    public function __construct(private Security $security)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response();
        }

        $memberModel = MemberModel::findByPk($user->id);
        $data = $memberModel->row();

        $form = $this->createForm(PersonalDataType::class, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = array_filter($form->getData());
            foreach ($data as $k => $v) {
                $memberModel->$k = $v;
            }

            $memberModel->save();

            return $this->redirectToRoute($request->get('_route'));
        }

        return $this->render('@FerienpassHostPortal/fragment/personal_data.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
