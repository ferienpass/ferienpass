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

namespace Ferienpass\CoreBundle\Dto;

use Ferienpass\CoreBundle\Entity\Host;

interface HostDto
{
    public static function fromEntity(Host $host = null): self;

    public function toEntity(Host $host = null): Host;
}