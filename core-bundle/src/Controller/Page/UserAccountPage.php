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

namespace Ferienpass\CoreBundle\Controller\Page;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Ferienpass\CoreBundle\Controller\Frontend\AbstractController;
use Ferienpass\CoreBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAccountPage extends AbstractController
{
    private array $fragments;

    public function __construct(iterable $fragments)
    {
        $this->fragments = $fragments instanceof \Traversable ? iterator_to_array($fragments) : $fragments;
    }

    public function __invoke(string $fragment, Request $request): Response
    {
        $this->initializeContaoFramework();

        if (!\array_key_exists($fragment, $this->fragments)) {
            throw new PageNotFoundException();
        }

        $this->checkToken();

        return $this->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.'.$fragment))
            ->getResponse()
        ;
    }
}
