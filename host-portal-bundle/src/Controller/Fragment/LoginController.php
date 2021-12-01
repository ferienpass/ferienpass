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

use Contao\CoreBundle\Security\Exception\LockedException;
use Ferienpass\CoreBundle\Form\UserLoginType;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractFragmentController
{
    public function __invoke(Request $request): Response
    {
        // If the form was submitted and the credentials were wrong, take the target
        // path from the submitted data as otherwise it would take the current page
        if ($request->isMethod('POST')) {
            $targetPath = base64_decode((string) $request->request->get('_target_path'), true);
        } elseif ($request->query->has('redirect')) {
            // We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
            if ($this->get('uri_signer')->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : ''))) {
                $targetPath = $request->query->get('redirect');
            }
        }

        /** @var AuthenticationException|null $exception */
        $exception = $this->get('security.authentication_utils')->getLastAuthenticationError();
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($exception instanceof LockedException) {
            $message = sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $exception->getLockedMinutes());
        } elseif ($exception instanceof InvalidTwoFactorCodeException) {
            $message = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
        } elseif ($exception instanceof AuthenticationException) {
            $message = $GLOBALS['TL_LANG']['ERR']['invalidLogin'];
        }

        if ($twoFactorEnabled = $authorizationChecker->isGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS')) {
            // Dispatch 2FA form event to prepare 2FA providers
            $token = $this->get('security.token_storage')->getToken();
            $event = new TwoFactorAuthenticationEvent($request, $token);
            $this->get('event_dispatcher')->dispatch($event, TwoFactorAuthenticationEvents::FORM);
        }

        if (null === ($targetPath ?? null)) {
            $targetPath = $request->getSchemeAndHttpHost().$request->getRequestUri();
        }

        $form = $this->createForm(UserLoginType::class, null, ['target_path' => base64_encode($targetPath ?? '')]);

        return $this->render('@FerienpassHostPortal/fragment/login.html.twig', [
            'message' => $message ?? null,
            'login' => $form->createView(),
            'twoFactorEnabled' => $twoFactorEnabled,
        ]);
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['security.authentication_utils'] = AuthenticationUtils::class;
        $services['security.authorization_checker'] = AuthorizationCheckerInterface::class;

        return $services;
    }
}
