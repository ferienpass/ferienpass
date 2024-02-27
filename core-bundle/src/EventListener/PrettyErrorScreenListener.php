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

namespace Ferienpass\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use Contao\StringUtil;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CmsBundle\Page\PageBuilderFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Twig\Environment;

#[AsEventListener]
class PrettyErrorScreenListener
{
    public function __construct(private readonly Environment $twig, private readonly PageBuilderFactory $pageBuilderFactory)
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

        if (!AcceptHeader::fromString($request->headers->get('Accept'))->has('text/html')) {
            return;
        }

        $this->handleException($event);
    }

    private function handleException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        switch (true) {
            case $exception instanceof NotFoundHttpException:
                $this->renderErrorScreenByType(404, $event);
                break;

            case $exception instanceof ServiceUnavailableHttpException:
                $this->renderErrorScreenByType(503, $event);

                if (!$event->hasResponse()) {
                    $this->renderTemplate('service_unavailable', 503, $event);
                }
                break;

            default:
                $this->renderErrorScreenByException($event);
        }
    }

    private function renderErrorScreenByType(int $type, ExceptionEvent $event): void
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        try {
            $request = $event->getRequest();
            $pageModel = $request->attributes->get('pageModel');

            try {
                $response = $this->getResponseFromPageHandler($type, $pageModel);
                $event->setResponse($response);
            } catch (ResponseException $e) {
                $event->setResponse($e->getResponse());
            } catch (\Throwable $e) {
                $event->setThrowable($e);
            }
        } finally {
            $processing = false;
        }
    }

    /**
     * Checks the exception chain for a known exception.
     */
    private function renderErrorScreenByException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = $this->getStatusCodeForException($exception);
        $template = null;

        // Look for a template
        do {
            if ($exception instanceof InvalidRequestTokenException) {
                $template = 'invalid_request_token';
            }
        } while (null === $template && null !== ($exception = $exception->getPrevious()));

        $this->renderTemplate($template ?: 'error', $statusCode, $event);
    }

    private function renderTemplate(string $template, int $statusCode, ExceptionEvent $event): void
    {
        $view = '@FerienpassCore/Error/'.$template.'.html.twig';
        $parameters = $this->getTemplateParameters($view, $statusCode, $event);
        $event->setResponse(new Response($this->twig->render($view, $parameters), $statusCode));
    }

    private function getTemplateParameters(string $view, int $statusCode, ExceptionEvent $event): array
    {
        $encoded = StringUtil::encodeEmail('info@ferienpass.online');

        return [
            'statusCode' => $statusCode,
            'statusName' => Response::$statusTexts[$statusCode],
            'template' => $view,
            'base' => $event->getRequest()->getBasePath(),
            'language' => LocaleUtil::formatAsLanguageTag($event->getRequest()->getLocale()),
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded,
            'exception' => $event->getThrowable()->getMessage(),
            'throwable' => $event->getThrowable(),
        ];
    }

    private function getResponseFromPageHandler(int $type, ?PageModel $pageModel): ?Response
    {
        return match (true) {
            404 === $type => $this->pageBuilderFactory->create($pageModel)
                ->addFragment('main', new FragmentReference('ferienpass.fragment.error404'))
                ->getResponse(),
            default => null,
        };
    }

    private function getStatusCodeForException(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return (int) $exception->getStatusCode();
        }

        return 500;
    }
}
