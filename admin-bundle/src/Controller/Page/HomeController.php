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

use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'admin_index')]
final class HomeController extends AbstractController
{
    private readonly array $fragments;

    public function __construct(iterable $fragments)
    {
        $this->fragments = $fragments instanceof \Traversable ? iterator_to_array($fragments) : $fragments;
    }

    public function __invoke(Request $request, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/home.html.twig', [
            'widgets' => array_map(fn (object $controller) => $controller::class, $this->fragments),
            'breadcrumb' => $breadcrumb->generate('Dashboard'),
        ]);
    }
}
