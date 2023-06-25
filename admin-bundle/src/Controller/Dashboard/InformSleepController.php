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

namespace Ferienpass\AdminBundle\Controller\Dashboard;

use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InformSleepController extends AbstractController
{
    public function __construct(private EditionRepository $editionRepository)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        /** @var Edition[] $inactiveEditions */
        $inactiveEditions = iterator_to_array($this->inactiveEditions());

        if (0 === \count($inactiveEditions)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/sleep.html.twig', [
            'editions' => $inactiveEditions,
        ]);
    }

    private function inactiveEditions(): \Generator
    {
        foreach ($this->editionRepository->findWithActiveTask('show_offers') as $edition) {
            if ($edition->getActiveTasks('application_system')->isEmpty()) {
                yield $edition;
            }
        }
    }
}
