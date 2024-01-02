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

use Contao\MemberModel;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditAccountType;
use Ferienpass\CoreBundle\Session\Flash;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/accounts/{!role}', defaults: ['role' => 'eltern'])]
final class AccountsController extends AbstractController
{
    private const ROLES = [
        'eltern' => 'ROLE_MEMBER',
        'veranstaltende' => 'ROLE_HOST',
        'admins' => 'ROLE_ADMIN',
    ];

    #[Route('', name: 'admin_accounts_index')]
    public function index(string $role, Request $request, Breadcrumb $breadcrumb, FactoryInterface $menuFactory): Response
    {
        if (!\in_array($role, array_keys(self::ROLES), true)) {
            throw $this->createNotFoundException('The role does not exist');
        }

        $items = MemberModel::findBy('role', self::ROLES[$role]);

        $nav = $menuFactory->createItem('Accounts');
        foreach (self::ROLES as $slug => $r) {
            $nav->addChild('accounts.'.$r, ['route' => 'admin_accounts_index', 'routeParameters' => ['role' => $slug]]);
        }

        return $this->render('@FerienpassAdmin/page/accounts/index.html.twig', [
            'items' => $items,
            'aside_nav' => $nav,
            'breadcrumb' => $breadcrumb->generate('accounts.title', 'accounts.'.self::ROLES[$role]),
        ]);
    }

    #[Route('/neu', name: 'admin_accounts_create')]
    #[Route('/{id}', name: 'admin_accounts_edit')]
    public function edit(string $role, ?int $id, Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        if (!\in_array($role, array_keys(self::ROLES), true)) {
            throw $this->createNotFoundException('The role does not exist');
        }

        if (null === $id) {
            $account = new MemberModel();
        } elseif (null === $account = MemberModel::findByPk($id)) {
            throw $this->createNotFoundException('The account does not exist');
        }

        $form = $formFactory->create(EditAccountType::class, $account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MemberModel $account */
            $account = $form->getData();

            $account->save();

            $flash->addConfirmation(text: new TranslatableMessage('editConfirm', domain: 'admin'));

            return $this->redirectToRoute('admin_accounts_edit', ['id' => $account->id]);
        }

        $breadcrumbTitle = $account->id ? sprintf('%s %s (bearbeiten)', $account->firstname, $account->lastname) : 'accounts.new';

        return $this->render('@FerienpassAdmin/page/accounts/edit.html.twig', [
            'item' => $account,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['accounts.title', ['route' => 'admin_accounts_index', 'routeParameters' => $role]], [$role, ['route' => 'admin_accounts_index', 'routeParameters' => $role]], $breadcrumbTitle),
        ]);
    }
}
