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

namespace Ferienpass\CoreBundle\Monolog;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\MessageLog;
use Ferienpass\CoreBundle\Monolog\Context\MessageContext;
use Ferienpass\CoreBundle\Repository\MessageLogRepository;
use Monolog\Handler\AbstractProcessingHandler;

class MessageLogHandler extends AbstractProcessingHandler
{
    public function __construct(private readonly MessageLogRepository $repository, private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function write(array $record): void
    {
        $messageContext = $record['context']['message'] ?? null;
        if (!$messageContext instanceof MessageContext) {
            return;
        }

        $message = $messageContext->getMessage();

        $uniqueId = $record['context']['id'];
        if (null !== $this->repository->findOneBy(['uniqueId' => $uniqueId])) {
            return;
        }

        $related = [];
        foreach ($message->getRelated() as $entity => $id) {
            $related[] = $this->em->getReference($entity, $id);
        }

        $logEntry = new MessageLog($uniqueId, $message::class, related: $related);

        $this->em->persist($logEntry);

        $this->em->flush();
    }
}
