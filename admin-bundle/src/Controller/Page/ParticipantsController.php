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

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Dto\BillingAddressDto;
use Ferienpass\AdminBundle\Form\EditParticipantType;
use Ferienpass\AdminBundle\Form\MultiSelectType;
use Ferienpass\AdminBundle\Form\SettleAttendancesType;
use Ferienpass\AdminBundle\Payments\ReceiptNumberGenerator;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/teilnehmende')]
final class ParticipantsController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private ReceiptNumberGenerator $numberGenerator)
    {
    }

    #[Route('', name: 'admin_participants_index')]
    public function index(ParticipantRepository $repository, Request $request, Breadcrumb $breadcrumb): Response
    {
        $qb = $repository->createQueryBuilder('e');
        $qb->orderBy('e.lastname');

        $paginator = (new Paginator($qb, 100))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassAdmin/page/participants/index.html.twig', [
            'createUrl' => $this->generateUrl('admin_participants_create'),
            'pagination' => $paginator,
            'breadcrumb' => $breadcrumb->generate('Teilnehmende'),
        ]);
    }

    #[Route('/neu', name: 'admin_participants_create')]
    #[Route('/{id}/bearbeiten', name: 'admin_participants_edit', requirements: ['id' => '\d+'])]
    public function edit(?Participant $participant, Request $request, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb): Response
    {
        $em = $this->doctrine->getManager();
        $form = $formFactory->create(EditParticipantType::class, $participant ?? new Participant());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($participant = $form->getData())) {
                $em->persist($participant);
            }

            $em->flush();

            return $this->redirectToRoute('admin_participants_edit', ['id' => $participant->getId()]);
        }

        $breadcrumbTitle = $participant ? $participant->getName().' (bearbeiten)' : 'participants.new';

        return $this->render('@FerienpassAdmin/page/participants/edit.html.twig', [
            'item' => $participant,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $breadcrumbTitle),
        ]);
    }

    #[Route('/{id}', name: 'admin_participants_attendances', requirements: ['id' => '\d+'])]
    public function attendances(Participant $participant, Request $request, AttendanceFacade $attendanceFacade, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb): Response
    {
        $items = $participant->getAttendancesNotWithdrawn();

        $add = $formFactory->createBuilder()
            ->add('offer', EntityType::class, [
                'class' => Offer::class,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('o')
                    ->leftJoin('o.dates', 'dates')
                    ->where('dates.begin >= CURRENT_TIMESTAMP()')
                ->andWhere('o.onlineApplication = 1'),
                'choice_label' => 'name',
            ])

            ->add('status', ChoiceType::class, [
                'choices' => [Attendance::STATUS_CONFIRMED, Attendance::STATUS_WAITLISTED, Attendance::STATUS_WAITING],
            ])
            ->add('notify', CheckboxType::class, [
                'required' => false,
                'label' => 'Teilnehmer:in benachrichtigen',
                'help' => 'Erfordert eine E-Mail-Adresse und/oder Mobilnummer',
            ])

            ->add('submit', SubmitType::class)
            ->getForm()
        ;

        $add->handleRequest($request);

        if ($add->isSubmitted() && $add->isValid()) {
            $attendanceFacade->create($add->get('offer')->getData(), $participant, status: 'waiting', notify: false);

            return $this->redirectToRoute('admin_participants_attendances', ['id' => $participant->getId()]);
        }

        /** @var Form $ms */
        $ms = $formFactory->create(MultiSelectType::class, options: [
            'buttons' => ['settle'],
            'items' => $items->filter(fn (Attendance $a) => !$a->isPaid())->toArray(),
        ]);

        $ms->handleRequest($request);
        if ($ms->isSubmitted() && $ms->isValid()) {
            switch ($ms->getClickedButton()->getName()) {
                case 'settle':
                    return $this->redirectToRoute('admin_attendances_settle', status: 307);
            }
        }

        return $this->render('@FerienpassAdmin/page/participants/attendances.html.twig', [
            'add' => $add,
            'ms' => $ms,
            'items' => $items,
            'participant' => $participant,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $participant->getName().' (Anmeldungen)'),
        ]);
    }

    #[Route('/abrechnen', name: 'admin_attendances_settle', methods: ['POST'])]
    public function settle(Request $request, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb, AttendanceRepository $attendanceRepository): Response
    {
        $attendances = $this->getAttendancesFromRequest($attendanceRepository, $request);
        $draftPayment = Payment::fromAttendances($attendances);

        $form = $formFactory->create(SettleAttendancesType::class, $dto = BillingAddressDto::fromPayment($draftPayment), ['attendances' => $attendances]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payment = new Payment($this->numberGenerator->generate());
            $dto->toPayment($payment);

            // TODO: add me
            // $payment->getItems()->map(fn(PaymentItem $item) => $item->getAttendance()->setPaid());

            $em = $this->doctrine->getManager();
            $em->persist($payment);
            $em->flush();

            return $this->redirectToRoute('admin_payments_receipt', ['id' => $payment->getId()]);
        }

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
