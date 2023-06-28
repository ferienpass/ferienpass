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
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\MultiSelectType;
use Ferienpass\AdminBundle\Payments\ReceiptNumberGenerator;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Entity\PaymentItem;
use Ferienpass\CoreBundle\Export\Payments\ReceiptExportInterface;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/zahlungen')]
final class PaymentsController extends AbstractController
{
    public function __construct(private ReceiptExportInterface $receiptExport)
    {
    }

    #[Route('', name: 'admin_payments_index')]
    public function index(PaymentRepository $repository, Request $request, Breadcrumb $breadcrumb): Response
    {
        $qb = $repository->createQueryBuilder('e');
        $qb->orderBy('e.createdAt', 'DESC');

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassAdmin/page/payments/index.html.twig', [
            'pagination' => $paginator,
            'breadcrumb' => $breadcrumb->generate('payments.title'),
        ]);
    }

    #[Route('/{id}', name: 'admin_payments_receipt', requirements: ['id' => '\d+'])]
    public function show(Payment $payment, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/payments/receipt.html.twig', [
            'payment' => $payment,
            'breadcrumb' => $breadcrumb->generate(['payments.title', ['route' => 'admin_payments_index']], 'Beleg #'.$payment->getReceiptNumber()),
        ]);
    }

    #[Route('/{id}/storno', name: 'admin_payments_reverse')]
    public function reverse(Payment $payment, Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, Breadcrumb $breadcrumb, ReceiptNumberGenerator $numberGenerator): Response
    {
        $items = $payment->getItems();

        /** @var Form $ms */
        $ms = $formFactory->create(MultiSelectType::class, options: [
            'buttons' => ['reverse'],
            'items' => $items->toArray(),
        ]);

        $ms->handleRequest($request);
        if ($ms->isSubmitted() && $ms->isValid() && 'reverse' === $ms->getClickedButton()->getName()) {
            return $this->reverseFormSubmit($ms, $payment, $numberGenerator, $em);
        }

        return $this->render('@FerienpassAdmin/page/payments/reverse.html.twig', [
            'ms' => $ms,
            'items' => $items,
            'breadcrumb' => $breadcrumb->generate(['payments.title', ['route' => 'admin_payments_index']], 'Beleg #'.$payment->getReceiptNumber(), 'Storno'),
        ]);
    }

    #[Route('/{id}.pdf', name: 'admin_payments_receipt_pdf', requirements: ['id' => '\d+'])]
    public function pdf(Payment $payment): Response
    {
        $path = $this->receiptExport->generate($payment);

        return $this->file($path, sprintf('beleg-%s.pdf', $payment->getId()));
    }

    private function reverseFormSubmit(Form $ms, Payment $payment, ReceiptNumberGenerator $numberGenerator, EntityManagerInterface $em): RedirectResponse
    {
        /** @var Collection $items */
        $items = $ms->get('items')->getData();
        if ($items->isEmpty()) {
            return $this->redirectToRoute('admin_payments_receipt', ['id' => $payment->getId()]);
        }

        $reversalPayment = new Payment($numberGenerator->generate());
        $reversalPayment->setBillingAddress($payment->getBillingAddress());
        $reversalPayment->setBillingEmail($payment->getBillingEmail());
        foreach ($items as $item) {
            $reversalPayment->addItem(new PaymentItem($item->getAttendance(), (-1) * $item->getAmount()));
        }

        // TODO: add me
        // $payment->getItems()->map(fn(PaymentItem $item) => $item->getAttendance()->setWithdrawn());

        $em->persist($reversalPayment);
        $em->flush();

        return $this->redirectToRoute('admin_payments_receipt', ['id' => $reversalPayment->getId()]);
    }
}
