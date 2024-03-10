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

namespace Ferienpass\CmsBundle\Security;

use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CmsBundle\Page\PageBuilderFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly PageBuilderFactory $pageBuilderFactory)
    {
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->pageBuilderFactory->create($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.error401'))
            ->getResponse()
        ;
    }
}
