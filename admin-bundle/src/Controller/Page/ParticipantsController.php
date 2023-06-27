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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Dto\BillingAddressDto;
use Ferienpass\AdminBundle\Form\CompoundType\PaymentItemType;
use Ferienpass\AdminBundle\Form\EditParticipantType;
use Ferienpass\AdminBundle\Payments\ReceiptNumberGenerator;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Entity\PaymentItem;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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
            'pagination' => $paginator,
            'breadcrumb' => $breadcrumb->generate('Teilnehmende'),
        ]);
    }

    #[Route('/{id}/bearbeiten', name: 'admin_participants_edit', requirements: ['id' => '\d+'])]
    public function edit(Participant $participant, Request $request, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb): Response
    {
        $em = $this->doctrine->getManager();
        $form = $formFactory->create(EditParticipantType::class, $participant);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->redirectToRoute('admin_participants_edit', ['id' => $participant->getId()]);
        }

        return $this->render('@FerienpassAdmin/page/participants/edit.html.twig', [
            'item' => $participant,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $participant->getName().' (bearbeiten)'),
        ]);
    }

    #[Route('/{id}', name: 'admin_participants_attendances', requirements: ['id' => '\d+'])]
    public function attendances(Participant $participant, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb): Response
    {
        $items = $participant->getAttendancesNotWithdrawn();

        return $this->render('@FerienpassAdmin/page/participants/attendances.html.twig', [
            'participant' => $participant,
            'items' => $items,
            'ms' => $this->multiSelectForm($formFactory, $items),
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], $participant->getName().' (Anmeldungen)'),
        ]);
    }

    #[Route('/abrechnen', name: 'admin_attendances_settle', methods: ['POST'])]
    public function settle(Request $request, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb, AttendanceRepository $attendanceRepository): Response
    {
        $em = $this->doctrine->getManager();

        $attendances = $this->getAttendancesFromRequest($attendanceRepository, $request);
        $draftPayment = Payment::fromAttendances($attendances);

        $dto = BillingAddressDto::fromPayment($draftPayment);

        $form = $formFactory->createNamedBuilder('settle', data: $dto, options: ['translation_domain' => 'admin', 'label_format' => 'payments.%name%',  'allow_extra_fields' => true])
            ->add('items', CollectionType::class, [
                'entry_options' => ['label' => false],
                'entry_type' => PaymentItemType::class,
                'allow_extra_fields' => true,
            ])
            ->add('address', TextareaType::class, [
                'attr' => ['rows' => 4],
            ])
            ->add('email', EmailType::class, options: ['required' => false])
            ->add('submit', SubmitType::class)
        ;

        foreach ($request->get('ms')['items'] as $i => $item) {
            $form->add('ms_'.$i, HiddenType::class, ['data' => $item, 'mapped' => false]);
        }

        $form = $form->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                dd($form->getErrors());
            }

            $payment = new Payment($this->numberGenerator->generate());
            $dto->toPayment($payment);

            $payment->getItems()->map(fn (PaymentItem $item) => $item->getAttendance()->setPaid());

            $em->persist($payment);
            $em->flush();

            $this->redirectToRoute('admin_payments_receipt', ['id' => $payment->getId()]);
        }

        return $this->render('@FerienpassAdmin/page/participants/settle.html.twig', [
            'form' => $form,
            'payment' => $draftPayment,
            'breadcrumb' => $breadcrumb->generate(['participants.title', ['route' => 'admin_participants_index']], 'Anmeldungen abrechnen'),
        ]);
    }

    private function multiSelectForm(FormFactoryInterface $formFactory, Collection $items = null): FormInterface
    {
        $options = [
            'class' => Attendance::class,
            'choice_label' => 'offer.name',
            'multiple' => true,
            'expanded' => true,
        ];

        if ($items) {
            $options['query_builder'] = fn (EntityRepository $er) => $er->createQueryBuilder('a')
                ->where('a.id in (:items)')
                ->setParameter('items', $items->filter(fn (Attendance $a) => !$a->isPaid()))
            ;
        }

        return $formFactory->createNamedBuilder('ms')
            ->add('items', EntityType::class, $options)
            ->add('submit', SubmitType::class, ['label' => 'Ausgewählte abrechnen'])
            ->setAction($this->generateUrl('admin_attendances_settle'))
            ->getForm()
        ;
    }

    /**
     * @return array<Attendance>
     */
    private function getAttendancesFromRequest(AttendanceRepository $attendanceRepository, Request $request): ?array
    {
        $ids = [];
        if ($request->request->has('settle')) {
            $a = $request->get('settle');
            foreach ($a as $b => $c) {
                if (str_starts_with($b, 'ms_')) {
                    $ids[] = (int) $c;
                }
            }
        }

        if (empty($ids) && !$request->request->has('ms')) {
            return null;
        }

        if (empty($ids)) {
            $ids = $request->get('ms')['items'];
        }
        $attendances = $attendanceRepository->findBy(['id' => $ids]);

        return (array) $attendances;
    }
}
