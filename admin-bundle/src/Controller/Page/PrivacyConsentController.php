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

use Contao\Config;
use Contao\FrontendUser;
use Contao\User;
use Doctrine\DBAL\Connection;
use Ferienpass\AdminBundle\State\PrivacyConsent as PrivacyConsentState;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\EqualTo;

class PrivacyConsentController extends AbstractController
{
    public function __construct(private Connection $connection, private PrivacyConsentState $consentState)
    {
    }

    public function __invoke(Request $request): Response
    {
        $error = null;
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('tstamp', 'statement_hash')
            ->from('tl_ferienpass_host_privacy_consent')
            ->where('member=:member')
            ->andWhere('type="sign"')
            ->setParameter('member', $user->id)
            ->setMaxResults(1)
            ->orderBy('tstamp', 'DESC')
            ->executeQuery()
            ->fetchAssociative();

        $isSigned = false !== $statement;

        if ($isSigned) {
            if ($this->consentState->hashIsValid($statement['statement_hash'])) {
                return $this->render('@FerienpassAdmin/fragment/privacy_consent.html.twig', [
                    'confirmation' => sprintf('Sie haben diese Erklärung am %s unterzeichnet.', date(Config::get('dateFormat'), (int) $statement['tstamp'])),
                    'signed' => $isSigned,
                    'statement' => $this->consentState->getFormattedConsentText(),
                ]);
            }

            $error = sprintf('Sie haben eine veraltete Version der Erklärung am %s unterzeichnet. Bitte unterzeichnen Sie die neue Version', date(Config::get('dateFormat'), (int) $statement['tstamp']));
        }

        $form = null;
        if (!$isSigned || $error) {
            $form = $this->consentForm($user);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->sign($form, $user);

                return $this->redirect($request->getRequestUri());
            }
        }

        return $this->render('@FerienpassAdmin/fragment/privacy_consent.html.twig', [
            'signed' => $isSigned,
            'error' => $error ?? null,
            'statement' => $this->consentState->getFormattedConsentText(),
            'form' => null !== $form ? $form->createView() : null,
        ]);
    }

    private function consentForm(FrontendUser $user): FormInterface
    {
        $formBuilder = $this->createFormBuilder(null, ['csrf_protection' => false])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('firstname', TextType::class, [
                'label' => 'tl_member.firstname.0',
                'translation_domain' => 'contao_tl_member',
                'attr' => ['placeholder' => $user->firstname],
                'constraints' => [
                    new EqualTo(['value' => $user->firstname]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'tl_member.lastname.0',
                'attr' => ['placeholder' => $user->lastname],
                'translation_domain' => 'contao_tl_member',
                'constraints' => [
                    new EqualTo(['value' => $user->lastname]),
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Unterzeichnen'])
        ;

        return $formBuilder->getForm();
    }

    private function sign(FormInterface $form, User $user): void
    {
        $this->connection->createQueryBuilder()
            ->insert('tl_ferienpass_host_privacy_consent')
            ->values([
                'tstamp' => '?',
                'member' => '?',
                'type' => '?',
                'form_data' => '?',
                'statement_hash' => '?',
            ])
            ->setParameters([
                time(),
                $user->id,
                'sign',
                json_encode($form->getData(), \JSON_THROW_ON_ERROR),
                sha1($this->consentState->getFormattedConsentText()),
            ])
            ->executeQuery()
        ;
    }
}
