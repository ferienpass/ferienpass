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

namespace Ferienpass\CmsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\EditionRepository;

class EditionOptionsListener
{
    public function __construct(private readonly EditionRepository $repository)
    {
    }

    #[AsCallback(table: 'tl_page', target: 'fields.edition.options')]
    public function onOptionsCallback(): array
    {
        /** @var Edition[] $editions */
        $editions = $this->repository->findBy([], ['name' => 'ASC']);

        $options = [];
        foreach ($editions as $edition) {
            $options[$edition->getId()] = $edition->getName();
        }

        return $options;
    }
}
