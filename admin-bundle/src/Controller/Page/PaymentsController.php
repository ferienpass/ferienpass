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
use Ferienpass\AdminBundle\Export\XlsxExport;
use Ferienpass\AdminBundle\Form\MultiSelectType;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Entity\PaymentItem;
use Ferienpass\CoreBundle\Export\Payments\ReceiptExportInterface;
use Ferienpass\CoreBundle\Payments\ReceiptNumberGenerator;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Ferienpass\CoreBundle\Session\Flash;
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

    #[Route('{_suffix}', name: 'admin_payments_index', defaults: ['_suffix' => ''])]
    public function index(PaymentRepository $repository, Breadcrumb $breadcrumb, string $_suffix, XlsxExport $xlsxExport): Response
    {
        $qb = $repository->createQueryBuilder('i');
        $qb->orderBy('i.createdAt', 'DESC');

        $_suffix = ltrim($_suffix, '.');
        if ('' !== $_suffix) {
            // TODO service-tagged exporter
            if ('xlsx' === $_suffix) {
                return $this->file($xlsxExport->generate($qb), 'zahlungen.xlsx');
            }
        }

        return $this->render('@FerienpassAdmin/page/payments/index.html.twig', [
            'qb' => $qb,
            'exports' => ['xlsx'],
            'searchable' => ['billingAddress', 'billingEmail', 'receiptNumber'],
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
    public function reverse(Payment $payment, Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash, ReceiptNumberGenerator $numberGenerator): Response
    {
        $items = $payment->getItems();

        /** @var Form $ms */
        $ms = $formFactory->create(MultiSelectType::class, options: [
            'buttons' => ['reverse'],
            'items' => $items->toArray(),
        ]);

        $ms->handleRequest($request);
        if ($ms->isSubmitted() && $ms->isValid() && 'reverse' === $ms->getClickedButton()->getName()) {
            return $this->reverseFormSubmit($ms, $payment, $numberGenerator, $em, $flash);
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

    private function reverseFormSubmit(Form $ms, Payment $payment, ReceiptNumberGenerator $numberGenerator, EntityManagerInterface $em, Flash $flash): RedirectResponse
    {
        /** @var Collection $items */
        $items = $ms->get('items')->getData();
        $items = $items->filter(fn (PaymentItem $pi) => $pi->getAttendance()->isPaid());

        if ($items->isEmpty()) {
            $flash->addError(text: 'Es wurde nichts storniert. Entweder wurde keine Auswahl getroffen, oder die Buchungen waren schon storniert.');

            return $this->redirectToRoute('admin_payments_receipt', ['id' => $payment->getId()]);
        }

        $reversalPayment = new Payment($numberGenerator->generate());
        $reversalPayment->setBillingAddress($payment->getBillingAddress());
        $reversalPayment->setBillingEmail($payment->getBillingEmail());
        foreach ($items as $item) {
            $reversalPayment->addItem(new PaymentItem($item->getAttendance(), (-1) * $item->getAmount()));
        }

        $payment->getItems()->map(fn (PaymentItem $item) => $item->getAttendance()->setPaid(false));

        $em->persist($reversalPayment);
        $em->flush();

        $flash->addConfirmation(text: 'Der Stornobeleg wurde erstellt.');

        return $this->redirectToRoute('admin_payments_receipt', ['id' => $reversalPayment->getId()]);
    }
}
