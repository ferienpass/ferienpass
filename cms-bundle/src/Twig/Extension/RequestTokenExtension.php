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

namespace Ferienpass\CmsBundle\Twig\Extension;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RequestTokenExtension extends AbstractExtension
{
    public function __construct(private readonly ContaoCsrfTokenManager $tokenManager, #[Autowire(param: 'contao.csrf_token_name')] private readonly string $tokenName)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('contao_request_token', $this->requestToken(...)),
        ];
    }

    public function requestToken(): string
    {
        return $this->tokenManager->getToken($this->tokenName)->getValue();
    }
}
