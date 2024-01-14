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

namespace Ferienpass\CmsBundle\Controller\Frontend;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/favicon.ico')]
final class FaviconIcoController
{
    public function __construct(#[Autowire('%contao.web_dir%')] private readonly string $webDir)
    {
    }

    public function __invoke(): Response
    {
        return new BinaryFileResponse($this->webDir.'/bundles/ferienpasscore/favicon/favicon.ico');
    }
}
