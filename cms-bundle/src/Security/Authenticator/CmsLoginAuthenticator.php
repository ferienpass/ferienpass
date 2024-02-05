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

namespace Ferienpass\CmsBundle\Security\Authenticator;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class CmsLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    private HttpUtils $httpUtils;
    private UserProviderInterface $userProvider;
    private AuthenticationSuccessHandlerInterface $successHandler;
    private AuthenticationFailureHandlerInterface $failureHandler;
    private array $options;
    private HttpKernelInterface $httpKernel;

    public function __construct(HttpUtils $httpUtils, UserProviderInterface $userProvider, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options)
    {
        $this->httpUtils = $httpUtils;
        $this->userProvider = $userProvider;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = array_merge([
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'enable_csrf' => false,
            'csrf_parameter' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ], $options);
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->request->has($this->options['username_parameter'])
            && $request->request->has($this->options['password_parameter'])
            && $request->request->has('user_login[submit]');
    }

    public function authenticate(Request $request): Passport
    {
        dd($request);
        $credentials = $this->getCredentials($request);

        $userBadge = new UserBadge($credentials['username'], $this->userProvider->loadUserByIdentifier(...));
        $passport = new Passport($userBadge, new PasswordCredentials($credentials['password']), [new RememberMeBadge()]);

        if ($this->options['enable_csrf']) {
            $passport->addBadge(new CsrfTokenBadge($this->options['csrf_token_id'], $credentials['csrf_token']));
        }

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    public function setHttpKernel(HttpKernelInterface $httpKernel): void
    {
        $this->httpKernel = $httpKernel;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $this->framework->initialize();

        $page = $this->getPageForRequest($request);
        $route = $this->pageRegistry->getRoute($page);
        $subRequest = $request->duplicate(null, null, $route->getDefaults());

        try {
            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
        } catch (ResponseException $e) {
            return $e->getResponse();
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return '';
    }

    private function getCredentials(Request $request): array
    {
        $credentials = [];
        $credentials['csrf_token'] = ParameterBagUtils::getRequestParameterValue($request, $this->options['csrf_parameter']);

        if ($this->options['post_only']) {
            $credentials['username'] = ParameterBagUtils::getParameterBagValue($request->request, $this->options['username_parameter']);
            $credentials['password'] = ParameterBagUtils::getParameterBagValue($request->request, $this->options['password_parameter']) ?? '';
        } else {
            $credentials['username'] = ParameterBagUtils::getRequestParameterValue($request, $this->options['username_parameter']);
            $credentials['password'] = ParameterBagUtils::getRequestParameterValue($request, $this->options['password_parameter']) ?? '';
        }

        if (!\is_string($credentials['username']) && !$credentials['username'] instanceof \Stringable) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['username']);

        if (!\is_string($credentials['password']) && (!\is_object($credentials['password']) || !method_exists($credentials['password'], '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['password_parameter'], \gettype($credentials['password'])));
        }

        return $credentials;
    }

    private function getPageForRequest(Request $request): PageModel
    {
        $page = $request->attributes->get('pageModel');
        $page->loadDetails();

        $page->protected = false;

        if (null === $this->tokenStorage->getToken()) {
            $pageAdapter = $this->framework->getAdapter(PageModel::class);
            $errorPage = $pageAdapter->findFirstPublishedByTypeAndPid('error_401', $page->rootId);

            if (null === $errorPage) {
                throw new PageNotFoundException('No error page found.');
            }

            $errorPage->loadDetails();
            $errorPage->protected = false;

            return $errorPage;
        }

        return $page;
    }
}
