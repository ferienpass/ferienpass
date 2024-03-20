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

use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Dto\BillingAddressDto;
use Ferienpass\AdminBundle\Export\XlsxExport;
use Ferienpass\AdminBundle\Form\EditParticipantType;
use Ferienpass\AdminBundle\Form\MultiSelectType;
use Ferienpass\AdminBundle\Form\SettleAttendancesType;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Entity\PaymentItem;
use Ferienpass\CoreBundle\Message\PaymentReceiptCreated;
use Ferienpass\CoreBundle\Payments\ReceiptNumberGenerator;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARTICIPANTS_ADMIN')]
#[Route('/teilnehmende')]
final class ParticipantsController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine, private readonly ReceiptNumberGenerator $numberGenerator)
    {
    }

    #[Route('{_suffix?}', name: 'admin_participants_index', requirements: ['_suffix' => '\.\w+'])]
    public function index(ParticipantRepository $repository, Breadcrumb $breadcrumb, ?string $_suffix, XlsxExport $xlsxExport): Response
    {
        $qb = $repository->createQueryBuilder('i');
        $qb->orderBy('i.lastname');

        // $filter = $this->filterFactory->create($qb)->applyFilter($request->query->all());

        $_suffix = ltrim((string) $_suffix, '.');
        if ('' !== $_suffix) {
            // TODO service-tagged exporter
            if ('xlsx' === $_suffix) {
                return $this->file($xlsxExport->generate($qb), 'teilnehmende.xlsx');
            }
        }

        return $this->render('@FerienpassAdmin/page/participants/index.html.twig', [
            'qb' => $qb,
            'exports' => ['xlsx'],
            'searchable' => ['firstname', 'lastname', 'email', 'mobile', 'phone'],
            'createUrl' => $this->generateUrl('admin_participants_create'),
            'breadcrumb' => $breadcrumb->generate('participants.title'),
        ]);
    }

    #[Route('/neu', name: 'admin_participants_create')]
    #[Route('/{id}/bearbeiten', name: 'admin_participants_edit', requirements: ['id' => '\d+'])]
    public function edit(?Participant $participant, Request $request, Breadcrumb $breadcrumb): Response
    {
        $em = $this->doctrine->getManager();
        $form = $this->createForm(EditParticipantType::class, $participant ?? new Participant());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($participant = $form->getData())) {
                $em->persist($participant);
            }

            $em->flush();

            return $this->redirectToRoute('admin_participants_edit', ['id' => $participant->getId()]);
        }

        $breadcrumbTitle = $participant ? $participant->getName().' (bearbeiten)' : 'participants.new';

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/participants/edit.html.twig', [
            'item' => $participant,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $breadcrumbTitle),
        ]);
    }

    #[Route('/{id}', name: 'admin_participants_attendances', requirements: ['id' => '\d+'])]
    public function attendances(Participant $participant, Request $request, Breadcrumb $breadcrumb): Response
    {
        $items = $participant->getAttendances();

        /** @var Form $ms */
        $ms = $this->createForm(MultiSelectType::class, options: [
            'buttons' => ['settle'],
            'items' => $items->toArray(),
        ]);

        $ms->handleRequest($request);
        if ($ms->isSubmitted() && $ms->isValid()) {
            if ('settle' === $ms->getClickedButton()->getName()) {
                return $this->redirectToRoute('admin_attendances_settle', status: 307);
            }
        }

        return $this->render('@FerienpassAdmin/page/participants/attendances.html.twig', [
            'ms' => $ms,
            'msPreferred' => $items->filter(fn (Attendance $a) => $a->isConfirmed() && !$a->isPaid())->toArray(),
            'items' => $items,
            'participant' => $participant,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $participant->getName().' (Anmeldungen)'),
        ]);
    }

    #[Route('/abrechnen', name: 'admin_attendances_settle', methods: ['POST'])]
    public function settle(Request $request, Breadcrumb $breadcrumb, AttendanceRepository $attendanceRepository, MessageBusInterface $messageBus): Response
    {
        $user = $this->getUser();
        $attendances = $this->getAttendancesFromRequest($attendanceRepository, $request);
        $attendances = array_filter($attendances, fn (Attendance $a) => !$a->isPaid());

        $draftPayment = Payment::fromAttendances($attendances);

        $form = $this->createForm(SettleAttendancesType::class, $dto = BillingAddressDto::fromPayment($draftPayment), ['attendances' => $attendances]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payment = new Payment($this->numberGenerator->generate(), $user);
            $dto->toPayment($payment);

            $payment->getItems()->map(fn (PaymentItem $item) => $item->getAttendance()->setPaid());

            $em = $this->doctrine->getManager();
            $em->persist($payment);
            $em->flush();

            $messageBus->dispatch(new PaymentReceiptCreated($payment->getId()));

            return $this->redirectToRoute('admin_payments_receipt', ['id' => $payment->getId()]);
        }

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/participants/settle.html.twig', [
            'form' => $form,
            'payment' => $draftPayment,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], 'Anmeldungen abrechnen'),
        ]);
    }

    /**
     * @return array<Attendance>
     */
    private function getAttendancesFromRequest(AttendanceRepository $attendanceRepository, Request $request): array
    {
        if ($request->request->has(MultiSelectType::FORM_NAME)) {
            $ids = $request->get(MultiSelectType::FORM_NAME)['items'] ?? [];
        }

        if ($request->request->has(SettleAttendancesType::FORM_NAME)) {
            $ids = $request->get(SettleAttendancesType::FORM_NAME)['ms'] ?? [];
        }

        if (null === ($ids ?? null)) {
            return [];
        }

        return $attendanceRepository->findBy(['id' => $ids]);
    }
}
