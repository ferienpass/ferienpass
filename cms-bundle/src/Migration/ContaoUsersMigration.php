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

class ContaoUsersMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return !$this->connection->createSchemaManager()->tablesExist(['users']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
        create table User
(
    id            int unsigned auto_increment primary key,
    email         varchar(180)                       not null,
    firstname     varchar(255)                       null,
    lastname      varchar(255)                       null,
    street        varchar(255)                       null,
    postal        varchar(16)                        null,
    city          varchar(255)                       null,
    phone         varchar(64)                        null,
    mobile        varchar(64)                        null,
    createdAt     datetime default CURRENT_TIMESTAMP not null,
    modifiedAt    datetime default CURRENT_TIMESTAMP not null,
    roles         json                               not null,
    password      varchar(255)                       null,
    disable       tinyint(1)                         not null,
    lastLogin     datetime                           null,
    superAdmin    tinyint(1)                         not null,
    editableRoles json                               not null,
    country       varchar(255)                       null
)
        ');

        $this->connection->executeStatement('
INSERT INTO User (id, firstname, lastname, email, street, postal, city, country, phone, mobile, createdAt, password, roles, disable, editableRoles, superAdmin)
SELECT id, firstname, lastname, email, street, postal, city, country, phone, mobile, FROM_UNIXTIME(dateAdded), password, IF(`groups` LIKE \'%"1"%\', JSON_ARRAY("ROLE_HOST"), JSON_ARRAY("ROLE_MEMBER")), disable, cast("[]" as json), admin FROM tl_member');

        return $this->createResult(true);
    }
}
