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

use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Export\Payments\ReceiptExportInterface;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/{id}.pdf', name: 'admin_payments_receipt_pdf', requirements: ['id' => '\d+'])]
    public function pdf(Payment $payment): Response
    {
        $path = $this->receiptExport->generate($payment);

        return $this->file($path, sprintf('beleg-%s.pdf', $payment->getId()));
    }
}
