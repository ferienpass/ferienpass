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

namespace Ferienpass\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ferienpass\CoreBundle\Repository\PaymentRepository;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    public const STATUS_PAID = 'paid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\JoinTable(name: 'PaymentItemAssociation')]
    #[ORM\JoinColumn(name: 'payment_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', unique: true)]
    #[ORM\ManyToMany(targetEntity: 'Ferienpass\CoreBundle\Entity\PaymentItem', cascade: ['persist'])]
    private Collection $items;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $totalAmount = 0;

    #[ORM\Column(type: 'text')]
    private ?string $billingAddress = null;

    #[ORM\Column(type: 'text', length: 255)]
    private ?string $billingEmail = null;

    #[ORM\Column(type: 'text', length: 255)]
    private ?string $receiptNumber;

    #[ORM\Column(type: 'text', length: 64)]
    private ?string $status = null;

    public function __construct(string $receiptNumber = null)
    {
        $this->receiptNumber = $receiptNumber;

        $this->status = self::STATUS_PAID;

        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    /**
     * @param array<Attendance> $attendances
     */
    public static function fromAttendances(?iterable $attendances, string $receiptNumber = null): static
    {
        $self = new self($receiptNumber);

        foreach ($attendances as $attendance) {
            $self->items->add(new PaymentItem($attendance, $attendance->getOffer()->getFee() ?? 0));
        }

        $self->calculateTotalAmount();

        return $self;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(string $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): void
    {
        $this->billingEmail = $billingEmail;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    private function calculateTotalAmount()
    {
        $this->totalAmount = array_sum(array_map(fn (PaymentItem $item) => $item->getAmount(), $this->items->toArray()));
    }
}