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
use Ferienpass\CoreBundle\Entity\EditionTask;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;

class NextStepsController extends AbstractController
{
    public function __construct(private EditionRepository $editionRepository)
    {
    }

    public function __invoke(): Response
    {
        foreach ($this->editionRepository->findCurrent() as $edition) {
            $editions[] = [
                'edition' => $edition,
                'steps' => $this->getSteps($edition),
            ];
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/next_steps.html.twig', [
            'editions' => $editions,
        ]);
    }

    private function getSteps(Edition $edition): array
    {
        $steps = [];

        if (null !== $task = $edition->getCurrentHostEditingStage()) {
            $steps[] = [
                'current' => true,
                'text' => new TranslatableMessage('Tragen Sie Ihre Angebote im Portal bis zum %deadline% ein', [
                    '%deadline%' => $task->getPeriodEnd()->format('d.m.Y H:i'),
                ]),
            ];
        }

        $showOfferPeriods = $edition->getTasksOfType('show_offers');

        if (!$edition->isEditableForHosts()) {
            if (($first = $edition->getHostEditingStages()->first()) && $first->getPeriodBegin() > new \DateTimeImmutable()) {
                $steps[] = [
                    'completed' => false,
                    'text' => new TranslatableMessage('Tragen Sie Ihre Angebote im Portal ab dem %begin% ein', [
                        '%begin%' => $first->getPeriodBegin()->format('d.m.Y'),
                    ]),
                ];
            } else {
                $steps[] = [
                    'completed' => true,
                    'text' => new TranslatableMessage('Angebote eintragen'),
                ];

                if (false !== ($period = $showOfferPeriods->first())
                    && $period->isUpcoming()) {
                    $steps[] = [
                        'current' => true,
                        'text' => 'Angebote werden von uns Korrektur gelesen',
                    ];
                }
            }
        }

        foreach ($showOfferPeriods as $task) {
            if ($task->isCompleted()) {
                $steps[] = [
                    'completed' => true,
                    'text' => 'Angebote online',
                ];
            } elseif ($task->isActive()) {
                $steps[] = [
                    'current' => true,
                    'text' => new TranslatableMessage('Angebote sind online bis %end%', [
                        '%end%' => $task->getPeriodEnd()->format('d.m.Y H:i'),
                    ]),
                ];
            } else {
                $steps[] = [
                    'text' => new TranslatableMessage('Angebote gehen online am %begin%', [
                        '%begin%' => $task->getPeriodBegin()->format('d.m.Y H:i'),
                    ]),
                ];
            }
        }

        foreach ($edition->getTasksOfType('publish_lists')->filter(fn (EditionTask $t) => $t->isUpcoming()) as $task) {
            $steps[] = [
                'text' => new TranslatableMessage('Die Teilnahmelisten stehen zur Verfügung ab %begin%', [
                    '%begin%' => $task->getPeriodBegin()->format('d.m.Y H:i'),
                ]),
            ];
        }

        $steps[] = [
            'completed' => $edition->getHoliday()->isCompleted(),
            'text' => 'Ferien sind rum!',
        ];

        return $steps;
    }
}
