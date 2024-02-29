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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class SetupCmsPagesMigration extends AbstractMigration
{
    private const REQUIRED_PAGES = [
        'account_deleted',
        'host_details',
        'offer_details',
        'offer_list',
        'lost_password',
        'registration_welcome',
        'registration_confirm',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_page'])) {
            return false;
        }

        $existingPages = $this->existingPages();
        $missingPages = array_diff(self::REQUIRED_PAGES, $existingPages);

        return \count($missingPages) > 0;
    }

    public function run(): MigrationResult
    {
        $time = time();
        $rootId = $this->connection->fetchOne('SELECT id FROM tl_page WHERE type="root"');
        if (false === $rootId) {
            $this->connection->executeStatement("INSERT INTO tl_page (type, tstamp, title, alias, published) VALUES ('root', $time, 'Ferienpass', 'ferienpass', 1)");
            $rootId = $this->connection->lastInsertId();
        }

        $existingPages = $this->existingPages();
        $missingPages = array_diff(self::REQUIRED_PAGES, $existingPages);

        foreach ($missingPages as $type) {
            $this->connection->executeStatement("INSERT INTO tl_page (pid, tstamp, title, alias, type, published, hide) VALUES ($rootId, $time, '$type', '$type', '$type', 1, 1); ");
        }

        $this->connection->executeStatement('UPDATE tl_page SET protected=0 WHERE protected=1');

        return $this->createResult(true);
    }

    private function existingPages(): array
    {
        return $this->connection->fetchFirstColumn('SELECT type FROM tl_page WHERE type IN (?)', [self::REQUIRED_PAGES], [ArrayParameterType::STRING]);
    }
}
