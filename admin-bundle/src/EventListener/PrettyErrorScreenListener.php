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

namespace Ferienpass\AdminBundle\EventListener;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\LayoutModel;
use Ferienpass\CoreBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Security;

class PrettyErrorScreenListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        return;
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('html' !== $request->getRequestFormat()) {
            return;
        }

        if (!str_starts_with((string) $request->getHttpHost(), 'veranstalter.')) {
            return;
        }

        if (!AcceptHeader::fromString($request->headers->get('Accept'))->has('text/html')) {
            return;
        }

        $this->handleException($event);
    }

    private function handleException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        try {
            $isBackendUser = $this->security->isGranted('ROLE_USER');
        } catch (AuthenticationCredentialsNotFoundException) {
            $isBackendUser = false;
        }

        if ($isBackendUser) {
            return;
        }

        switch (true) {
            case $exception instanceof UnauthorizedHttpException:
                $this->renderErrorScreenByType(401, $event);
                break;

            case $exception instanceof AccessDeniedHttpException:
                $this->renderErrorScreenByType(403, $event);
                break;

            case $exception instanceof NotFoundHttpException:
                $this->renderErrorScreenByType(404, $event);
                break;
        }
    }

    private function renderErrorScreenByType(int $type, ExceptionEvent $event): void
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        if (null !== ($response = $this->getResponseFromPageHandler($type, $event->getRequest()))) {
            $event->setResponse($response);
        }

        $processing = false;
    }

    private function getResponseFromPageHandler(int $type, Request $request): ?Response
    {
        try {
            switch (true) {
                case 401 === $type:
                    $layout = LayoutModel::findBy('alias', 'splash');
                    $pageModel = $request->attributes->get('pageModel');
                    $pageModel->layout = $layout->id;

                    return $this->pageBuilderFactory->create($pageModel)
                        ->addFragment('main', new FragmentReference('ferienpass.fragment.host.login'))
                        ->getResponse();
                    break;

                case 403 === $type:
                    return $this->pageBuilderFactory->create($request->attributes->get('pageModel'))
                        ->addFragment('main', new FragmentReference('ferienpass.fragment.host.error403'))
                        ->getResponse();

                case 404 === $type:
                    return $this->for;
            }
        } catch (ResponseException $e) {
            return $e->getResponse();
        } /*catch (\Exception $e) {
            return null;
        }*/

        return null;
    }
}
