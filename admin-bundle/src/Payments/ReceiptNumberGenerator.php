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

namespace Ferienpass\AdminBundle\Payments;

use Ferienpass\CoreBundle\Repository\PaymentRepository;

class ReceiptNumberGenerator
{
    private string $prefix = '';

    private string $suffix = '';

    private int $length = 0;

    public function __construct(string $prefix, string $suffix, private PaymentRepository $paymentRepository)
    {
    }

    public function generate(): string
    {
        $offset1 = mb_strlen($this->prefix) + 1;
        $offset2 = mb_strlen($this->suffix) * (-1);

        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb->select("substring(substring(p.receiptNumber, $offset1), $offset2) AS HIDDEN docNumber");

        if ($this->prefix) {
            $qb->where($qb->expr()->like('p.receiptNumber', "$this->prefix%"));
        }

        if ($this->suffix) {
            $qb->where($qb->expr()->like('p.receiptNumber', "%$this->suffix"));
        }

        $qb->orderBy('docNumber', 'DESC');

        $result = $qb->getQuery()->getOneOrNullResult();
        $docNumber = null === $result ? 0 : $result->docNumber;

        return sprintf('%s%s%s',
            $this->prefix,
            str_pad((string) ++$docNumber, $this->length, '0', \STR_PAD_LEFT),
            $this->suffix
        );
    }
}
