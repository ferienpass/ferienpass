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

namespace Ferienpass\HostPortalBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\State\PrivacyConsent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MissingPrivacyStatementAlert
{
    private ContaoFramework $contaoFramework;
    private ScopeMatcher $scopeMatcher;
    private PrivacyConsent $privacyConsent;
    private UrlGeneratorInterface $router;

    public function __construct(ContaoFramework $contaoFramework, PrivacyConsent $privacyConsent, ScopeMatcher $scopeMatcher, UrlGeneratorInterface $router)
    {
        $this->contaoFramework = $contaoFramework;
        $this->scopeMatcher = $scopeMatcher;
        $this->privacyConsent = $privacyConsent;
        $this->router = $router;
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMasterRequest($event) || $event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $this->contaoFramework->initialize(true);

        $user = $this->contaoFramework->createInstance(FrontendUser::class);

        // Is member a host?
        if (false === $user->isMemberOf(1)) {
            return;
        }

        if ($this->privacyConsent->isSignedFor((int) $user->id)) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if (!$session instanceof Session) {
            return;
        }

        $session->getFlashBag()->add(...Flash::infoBanner()
            ->text('Bitte unterzeichnen Sie unsere Datenschutzerklärung')
            ->href($this->router->generate('host_privacy_consent'))
            ->create()
        );
    }
}
