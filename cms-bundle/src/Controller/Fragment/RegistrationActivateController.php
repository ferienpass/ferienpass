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

namespace Ferienpass\CmsBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendUser;
use Contao\MemberModel;
use Ferienpass\CoreBundle\Message\AccountActivated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Translation\TranslatableMessage;

class RegistrationActivateController extends AbstractController
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage, private readonly LoggerInterface $logger, private readonly EventDispatcherInterface $eventDispatcher, private readonly OptIn $optIn, private readonly TokenChecker $tokenChecker, private readonly MessageBusInterface $messageBus)
    {
    }

    public function __invoke(Request $request): Response
    {
        if ((null === $token = (string) $request->query->get('token')) || !str_starts_with($token, 'reg-')) {
            throw new PageNotFoundException('Invalid token');
        }

        if (null !== $this->tokenChecker->getFrontendUsername()) {
            return $this->redirectToRoute('registration_welcome');
        }

        // Find an unconfirmed token with only one related record
        if ((!$optInToken = $this->optIn->find($token)) || !$optInToken->isValid()
            || 1 !== \count($relatedRecords = $optInToken->getRelatedRecords())
            || 'tl_member' !== key($relatedRecords)
            || 1 !== (is_countable($arrIds = current($relatedRecords)) ? \count($arrIds = current($relatedRecords)) : 0)
            || (!$memberModel = MemberModel::findByPk($arrIds[0]))) {
            return $this->render('@FerienpassCore/Fragment/message.html.twig', ['error' => new TranslatableMessage('MSC.invalidToken', [], 'contao_default')]);
        }

        if ($optInToken->isConfirmed()) {
            return $this->redirectToRoute('registration_welcome');
        }

        if ($optInToken->getEmail() !== $memberModel->email) {
            return $this->render('@FerienpassCore/Fragment/message.html.twig', ['error' => new TranslatableMessage('MSC.tokenEmailMismatch', [], 'contao_default')]);
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $memberModel->disable = '0';
        $memberModel->save();

        $optInToken->confirm();

        $this->logger->info('User account ID {id} has been activated', ['id' => $memberModel->id]);

        // $this->loginUser($memberModel->username, $request);

        $this->messageBus->dispatch(new AccountActivated((int) $memberModel->id));

        return $this->redirectToRoute('registration_welcome');
    }

    private function loginUser(string $username, Request $request): void
    {
        //        try {
        //            $user = $this->userProvider->loadUserByIdentifier($username);
        //        } catch (UsernameNotFoundException) {
        //            return;
        //        }

        if (!$user instanceof FrontendUser) {
            return;
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException) {
            return;
        }

        $usernamePasswordToken = new UsernamePasswordToken($user, 'frontend', $user->getRoles());
        $this->tokenStorage->setToken($usernamePasswordToken);

        $event = new InteractiveLoginEvent($request, $usernamePasswordToken);
        $this->eventDispatcher->dispatch($event);

        $this->logger->info(sprintf('User "%s" was logged in automatically after account activation', $username));

        $request->request->set('_target_path', base64_encode($request->getRequestUri()));

        $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $usernamePasswordToken);
    }
}
