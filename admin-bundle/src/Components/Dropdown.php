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

namespace Ferienpass\AdminBundle\Components;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Dropdown
{
    #[LiveProp]
    public ?string $toggleLabel = null;
    #[LiveProp]
    public ?string $toggleSize = null;

    #[LiveProp]
    public ?string $dropdownClass = null;

    #[LiveProp]
    public string $position = 'right';
}
