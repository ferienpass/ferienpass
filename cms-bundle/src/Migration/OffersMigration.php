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

namespace Ferienpass\CmsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class OffersMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return !$this->connection->createSchemaManager()->tablesExist(['Offer']) || !\in_array('published', $this->connection->createSchemaManager()->listTableColumns('Offer'), true) || $this->connection->executeQuery('SELECT COUNT(id) FROM Offer WHERE published=1 AND state="draft"')->fetchOne();
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('update Offer set state="published" where published=1');
        $this->connection->executeStatement('update Offer set state="cancelled" where cancelled=1');

        return $this->createResult(true);
    }
}
