<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Terminal42\ContaoDamIntegrator\Integration\Bynder\BynderIntegration;

class UpgradeFromContaoBynderDbafsMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_files'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_files');

        // Migration already done
        if (isset($columns['dam_asset_id'])) {
            return false;
        }

        return isset($columns['bynder_id']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            ALTER TABLE
                tl_files
            ADD
                dam_asset_id VARCHAR(64) DEFAULT NULL,
            ADD
                dam_asset_hash VARCHAR(64) DEFAULT NULL,
            ADD
                dam_asset_integration VARCHAR(64) DEFAULT NULL
        ');

        $this->connection->executeStatement(
            'UPDATE tl_files SET dam_asset_id = bynder_id, dam_asset_hash = bynder_hash, dam_asset_integration = :integration WHERE bynder_id IS NOT NULL',
            [
                'integration' => BynderIntegration::getKey(),
            ],
        );

        return $this->createResult(true);
    }
}
