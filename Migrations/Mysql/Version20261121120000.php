<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Moves the tables from the Sitegeist.SlopMachine to the Shel.Neos.Mcp namespace.
 *
 * Doctrine tracks migration versions by class name, so the versions that created the old
 * tables may or may not have run in an installation; the rename branch covers upgrades from
 * the renamed fork, the create branch covers installations without any of the tables. The
 * constraint and index names of the original schema are repeated in the create statements,
 * so that both paths end in the same schema.
 *
 * The tables are checked with plain queries instead of the given $schema: full introspection
 * can fail in installations where an unrelated package still carries column types the
 * registered Doctrine version cannot map.
 */
final class Version20261121120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames the tables of Sitegeist.SlopMachine to the names of Shel.Neos.Mcp, or creates them if the installation has none';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on MySQL / MariaDB.'
        );

        foreach ($this->tables() as $table) {
            if ($this->tableExists($table['old'])) {
                $this->abortIf(
                    $this->tableExists($table['new']),
                    sprintf('Cannot rename "%s": the table "%s" already exists.', $table['old'], $table['new'])
                );
                $this->addSql(sprintf('RENAME TABLE %s TO %s', $table['old'], $table['new']));
                continue;
            }
            if (!$this->tableExists($table['new'])) {
                foreach ($table['create'] as $sql) {
                    $this->addSql($sql);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on MySQL / MariaDB.'
        );

        // Renaming back is the inverse of both branches: a rollback of the versions that
        // created the tables then removes them from there.
        foreach ($this->tables() as $table) {
            if ($this->tableExists($table['new']) && !$this->tableExists($table['old'])) {
                $this->addSql(sprintf('RENAME TABLE %s TO %s', $table['new'], $table['old']));
            }
        }
    }

    private function tableExists(string $tableName): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        );

        return (int)$count > 0;
    }

    /**
     * @return list<array{old: string, new: string, create: list<string>}>
     */
    private function tables(): array
    {
        return [
            [
                'old' => 'sitegeist_slopmachine_domain_model_agentassignment',
                'new' => 'shel_neos_mcp_domain_model_agentassignment',
                'create' => [
                    'CREATE TABLE shel_neos_mcp_domain_model_agentassignment (agentid VARCHAR(255) NOT NULL, user VARCHAR(40) DEFAULT NULL, INDEX IDX_D8527DD48D93D649 (user), PRIMARY KEY(agentid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                    'ALTER TABLE shel_neos_mcp_domain_model_agentassignment ADD CONSTRAINT FK_D8527DD48D93D649 FOREIGN KEY (user) REFERENCES neos_neos_domain_model_user (persistence_object_identifier)',
                ],
            ],
            [
                'old' => 'sitegeist_slopmachine_domain_model_instruction',
                'new' => 'shel_neos_mcp_domain_model_instruction',
                'create' => [
                    'CREATE TABLE shel_neos_mcp_domain_model_instruction (persistence_object_identifier VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, instructions LONGTEXT NOT NULL, position INTEGER NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
                ],
            ],
        ];
    }
}
