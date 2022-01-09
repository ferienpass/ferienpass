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

use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Error403Controller extends AbstractFragmentController
{
    public function __construct(private string $adminEmail)
    {
    }

    public function __invoke(Request $request): Response
    {
        return $this->render('@FerienpassHostPortal/fragment/error403.html.twig', [
            'mailHref' => StringUtil::encodeEmail('mailto:'.$this->adminEmail),
        ]);
    }
}
