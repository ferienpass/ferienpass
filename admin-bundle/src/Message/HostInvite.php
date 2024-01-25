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

namespace Ferienpass\AdminBundle\Message;

class HostInvite
{
    public function __construct(private readonly string $email, private readonly int $hostId, private readonly int $userId)
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getHostId(): int
    {
        return $this->hostId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
