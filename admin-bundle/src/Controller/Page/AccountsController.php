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
use Ferienpass\AdminBundle\Form\EditAccountType;
use Ferienpass\AdminBundle\Form\Filter\AccountsFilter;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Ferienpass\CoreBundle\Session\Flash;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/accounts/{!role}', defaults: ['role' => 'eltern'])]
final class AccountsController extends AbstractController
{
    public const ROLES = [
        'eltern' => 'ROLE_MEMBER',
        'veranstaltende' => 'ROLE_HOST',
        'admins' => 'ROLE_ADMIN',
    ];

    #[Route('{_suffix?}', name: 'admin_accounts_index', requirements: ['role' => '\w+', '_suffix' => '\.\w+'])]
    public function index(string $role, ?string $_suffix, UserRepository $repository, Breadcrumb $breadcrumb, FactoryInterface $menuFactory, XlsxExport $xlsxExport): Response
    {
        if (!\in_array($role, array_keys(self::ROLES), true)) {
            throw $this->createNotFoundException('The role does not exist');
        }

        // Only super admins can edit admins
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && 'ROLE_ADMIN' === self::ROLES[$role]) {
            throw $this->createAccessDeniedException();
        }

        $actualRole = self::ROLES[$role];

        $qb = $repository->createQueryBuilder('i')
            ->where("JSON_SEARCH(i.roles, 'one', :role) IS NOT NULL")
            ->setParameter('role', $actualRole)
        ;

        $nav = $menuFactory->createItem('accounts.roles');
        foreach (self::ROLES as $slug => $r) {
            if ('ROLE_ADMIN' === $r && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                continue;
            }

            $nav->addChild('accounts.'.$r, [
                'route' => 'admin_accounts_index',
                'routeParameters' => ['role' => $slug],
                'current' => $slug === $role,
            ]);
        }

        $_suffix = ltrim((string) $_suffix, '.');
        if ('' !== $_suffix) {
            // TODO service-tagged exporter
            if ('xlsx' === $_suffix) {
                return $this->file($xlsxExport->generate($qb), $role.'.xlsx');
            }
        }

        return $this->render('@FerienpassAdmin/page/accounts/index.html.twig', [
            'qb' => $qb,
            'filterType' => AccountsFilter::class,
            'exports' => ['xlsx'],
            'role' => $actualRole,
            'searchable' => ['firstname', 'lastname', 'email'],
            'createUrl' => $this->generateUrl('admin_accounts_create', ['role' => $role]),
            'headline' => 'accounts.'.$actualRole,
            'aside_nav' => $nav,
            'breadcrumb' => $breadcrumb->generate('accounts.title', 'accounts.'.self::ROLES[$role]),
        ]);
    }

    #[Route('/neu', name: 'admin_accounts_create')]
    #[Route('/{id}', name: 'admin_accounts_edit')]
    public function edit(string $role, ?User $user, Request $request, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        if (!\in_array($role, array_keys(self::ROLES), true)) {
            throw $this->createNotFoundException('The role does not exist');
        }

        // Only super admins can edit/create admins
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && 'ROLE_ADMIN' === self::ROLES[$role]) {
            throw $this->createAccessDeniedException();
        }

        if (null === $user) {
            $user = new User();
            $user->setRoles([self::ROLES[$role]]);
        }

        if (!\in_array(self::ROLES[$role], $user->getRoles(), true)) {
            throw $this->createNotFoundException('The account does not exist');
        }

        $form = $this->createForm(EditAccountType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($user = $form->getData())) {
                $em->persist($user);
            }

            $em->flush();

            $flash->addConfirmation(text: new TranslatableMessage('editConfirm', domain: 'admin'));

            return $this->redirectToRoute('admin_accounts_edit', ['id' => $user->getId()]);
        }

        $breadcrumbTitle = $user->getId() ? sprintf('%s (bearbeiten)', $user->getName()) : 'accounts.new';

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/accounts/edit.html.twig', [
            'item' => $user,
            'headline' => $user->getId() ? $user->getName() : 'accounts.new',
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['accounts.title', ['route' => 'admin_accounts_index', 'routeParameters' => ['role' => $role]]], ['accounts.'.self::ROLES[$role], ['route' => 'admin_accounts_index', 'routeParameters' => ['role' => $role]]], $breadcrumbTitle),
        ]);
    }
}
