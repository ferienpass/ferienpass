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

namespace Ferienpass\AdminBundle\Controller\Page;

use Contao\CoreBundle\OptIn\OptInInterface;
use Contao\Input;
use Contao\MemberModel;
use Ferienpass\CoreBundle\Form\UserLostPasswordType;
use Ferienpass\CoreBundle\Ux\Flash;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/passwort-vergessen', name: 'admin_lost_password')]
final class LostPasswordController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private OptInInterface $optIn, private RouterInterface $router, private PasswordHasherInterface $passwordHasher, private FormFactoryInterface $formFactory)
    {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->query->has('token') && ($token = (string) $request->query->get('token'))
            && str_starts_with($token, 'pw-')) {
            return $this->setNewPassword($request);
        }

        $form = $this->formFactory->create(UserLostPasswordType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $memberModel = MemberModel::findActiveByEmailAndUsername($data['email'] ?? '');
            if (null === $memberModel) {
                sleep(2); // Wait 2 seconds while brute forcing :)
                $form->addError(new FormError($GLOBALS['TL_LANG']['MSC']['accountNotFound']));
            } else {
                $this->sendPasswordLink($memberModel, $request->attributes->get('_route'));

                $this->addFlash(...Flash::confirmationModal()->headline('Passwort-Link versendet')->text('Bitte überprüfen Sie Ihre E-Mails')->linkText('Zur Startseite')->create());

                return $this->redirectToRoute($request->attributes->get('_route'));
            }
        }

        return $this->render('@FerienpassAdmin/page/login/lost_password.html.twig', [
            'form' => $form,
        ]);
    }

    protected function sendPasswordLink(MemberModel $memberModel, string $route): void
    {
        /** @var Notification|null $notification */
        $notification = Notification::findOneBy('type', 'member_password');
        if (null === $notification) {
            throw new \RuntimeException('No notification for password reset found!');
        }

        $optInToken = $this->optIn->create('pw', $memberModel->email, ['tl_member' => [(int) $memberModel->id]]);

        $tokens = [];

        // Add member tokens
        foreach ($memberModel->row() as $k => $v) {
            $tokens['member_'.$k] = $v;
        }

        $tokens['recipient_email'] = $memberModel->email;
        $tokens['link'] = $this->router->generate($route, ['token' => $optInToken->getIdentifier()], RouterInterface::ABSOLUTE_URL);

        $notification->send($tokens, $GLOBALS['TL_LANGUAGE']);

        $this->logger->info('A new password has been requested for user ID '.$memberModel->id);
    }

    protected function setNewPassword(Request $request): Response
    {
        // Find an unconfirmed token with only one related record
        if ((!$optInToken = $this->optIn->find(Input::get('token')))
            || !$optInToken->isValid()
            || 1 !== \count($related = $optInToken->getRelatedRecords())
            || 'tl_member' !== key($related)
            || 1 !== (is_countable($arrIds = current($related)) ? \count($arrIds = current($related)) : 0)
            || (!$memberModel = MemberModel::findByPk($arrIds[0]))) {
            return $this->render(
                '@FerienpassCore/Fragment/message.html.twig',
                ['error' => $GLOBALS['TL_LANG']['MSC']['invalidToken']]
            );
        }

        if ($optInToken->isConfirmed()) {
            return $this->render(
                '@FerienpassCore/Fragment/message.html.twig',
                ['error' => $GLOBALS['TL_LANG']['MSC']['tokenConfirmed']]
            );
        }

        if ($optInToken->getEmail() !== $memberModel->email) {
            return $this->render(
                '@FerienpassCore/Fragment/message.html.twig',
                ['error' => $GLOBALS['TL_LANG']['MSC']['tokenEmailMismatch']]
            );
        }

        $form = $this->formFactory->create(UserLostPasswordType::class, $memberModel, ['reset_password' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $memberModel->password = $this->passwordHasher->hash($memberModel->password ?? '');
            $memberModel->tstamp = time();
            $memberModel->locked = 0;

            $memberModel->save();
            $optInToken->confirm();

            $this->addFlash(...Flash::confirmationModal()->headline('Passwort-Reset erfolgreich')->text('Sie können sich nun mit Ihrem neuen Passwort anmelden.')->linkText('Zur Startseite')->create());

            return $this->render('@FerienpassAdmin/page/login/lost_password.html.twig', [
                'form' => $form,
            ]);
        }

        return $this->render('@FerienpassAdmin/page/login/lost_password.html.twig', [
            'form' => $form,
        ]);
    }
}
