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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FrontendUser;
use Ferienpass\AdminBundle\State\PrivacyConsent;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

final class MissingPrivacyStatementAlert
{
    public function __construct(private readonly ContaoFramework $contaoFramework, private readonly PrivacyConsent $privacyConsent, private readonly ScopeMatcher $scopeMatcher, private readonly UrlGeneratorInterface $router, private readonly Security $security)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event) || $event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $this->contaoFramework->initialize(true);

        $user = $this->contaoFramework->createInstance(FrontendUser::class);

        // Is member a host?
        if (false === $this->security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, 1)) {
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
