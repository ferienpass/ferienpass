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

namespace Ferienpass\CoreBundle\MessageHandler;

use Contao\Model;
use Ferienpass\CoreBundle\Export\Offer\ICal\ICalExport;
use Ferienpass\CoreBundle\Message\AttendanceCreated;
use Ferienpass\CoreBundle\Messenger\NotificationHandlerResult;
use Ferienpass\CoreBundle\Monolog\Context\NotificationContext;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use NotificationCenter\Model\Notification;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WhenAttendanceCreatedThenNotify implements MessageHandlerInterface
{
    private ICalExport $iCal;
    private TranslatorInterface $translator;
    private AttendanceRepository $attendanceRepository;
    private UrlGeneratorInterface $router;

    public function __construct(ICalExport $iCal, TranslatorInterface $translator, AttendanceRepository $attendanceRepository, UrlGeneratorInterface $router)
    {
        $this->iCal = $iCal;
        $this->translator = $translator;
        $this->attendanceRepository = $attendanceRepository;
        $this->router = $router;
    }

    public function __invoke(AttendanceCreated $message): ?NotificationHandlerResult
    {
        if (!$message->shallNotify()) {
            return null;
        }

        $attendance = $this->attendanceRepository->find($message->getAttendance());
        if (null === $attendance || !$attendance->isConfirmed()) {
            return null;
        }

        $offer = $attendance->getOffer();
        $participant = $attendance->getParticipant();
        if (null === $participant) {
            return null;
        }

        /** @var Notification $notification */
        $notification = Notification::findOneByType('attendance_created_confirmed');
        if (null === $notification) {
            return null;
        }

        $result = [];
        $language = $GLOBALS['TL_LANGUAGE'];

        $tokens = [];

        $tokens['offer'] = $offer->getId();
        $tokens['participant'] = $participant->getId();

        $tokens['attachment'] = $this->iCal->generate([$offer]);
        $tokens['footer_reason'] = $this->translator->trans('email.reason.applied', [], null, $language);

        $tokens['link'] = $this->router->generate('applications', [], UrlGeneratorInterface::ABSOLUTE_URL);

        /** @var Notification|Model $notification */
        foreach ($notification->send($tokens, $language) as $messageId => $success) {
            $result[] = new NotificationContext((int) $notification->id, (int) $messageId, $tokens, $language, $success);
        }

        return new NotificationHandlerResult($result);
    }
}
