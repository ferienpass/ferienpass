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

namespace Ferienpass\CmsBundle\Controller\Frontend;

use Ferienpass\CoreBundle\Export\Offer\ICal\ICalExport;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['token_check' => false])]
class ExportAttendancesController extends AbstractController
{
    public function __construct(#[Autowire('%kernel.secret%')] private readonly string $secret)
    {
    }

    #[Route(path: '/anmeldungen-{memberId}-{token}.{_format}', requirements: ['memberId' => '\d+'], defaults: ['format' => 'ics'])]
    public function __invoke(int $memberId, string $token, string $_format, Request $request, ICalExport $iCal, UserRepository $userRepository, OfferRepositoryInterface $offerRepository)
    {
        $user = $userRepository->find($memberId);
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $expectedToken = hash('ripemd128', implode('', [$user->getId(), $_format, $this->secret]));
        $expectedToken = substr($expectedToken, 0, 8);
        if (false === hash_equals($expectedToken, $token)) {
            throw $this->createAccessDeniedException();
        }

        if ('ics' !== $_format) {
            throw $this->createNotFoundException();
        }

        $offers = $offerRepository->createQueryBuilder('o')
            ->innerJoin('o.attendances', 'a')
            ->innerJoin('p.participants', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;

        $response = new BinaryFileResponse($iCal->generate($offers));
        $response->headers->set('Content-Type', 'text/calendar');

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'cal.ics');

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
