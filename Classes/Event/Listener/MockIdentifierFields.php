<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Event\Listener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsEventListener(
    identifier: 'km2/mock-data/mock-identifier-fields',
    after: 'content-blocks-sql'
)]
final readonly class MockIdentifierFields
{
    /**
     * Tables staring with cache_ will always be ignored.
     *
     * @var list<string>
     */
    private array $ignoreTables;

    /**
     * @var list<string>
     */
    private array $coreTables;

    public function __construct(private ConnectionPool $connectionPool, private SchemaMigrator $schemaMigrator)
    {
        $this->ignoreTables = [
            'be_sessions',
            'fe_sessions',
            'sys_be_shortcuts',
            'sys_csp_resolution',
            'sys_history',
            'sys_http_report',
            'sys_lockedrecords',
            'sys_log',
            'sys_messenger_messages',
            'sys_news',
            'sys_refindex',
            'tx_extensionmanager_domain_model_extension',
            'tx_scheduler_task',
            'tx_scheduler_task_group',
        ];

        $this->coreTables = [
            'be_groups',
            'be_users',
            'fe_groups',
            'fe_users',
            'pages',
            'sys_category',
            'sys_category_record_mm',
            'sys_file',
            'sys_filemounts',
            'sys_file_collection',
            'sys_file_metadata',
            'sys_file_reference',
            'sys_file_storage',
            'tt_content',
        ];
    }

    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        $sqlData = $event->getSqlData();
        $connection = $this->connectionPool->getConnectionByName('Default');
        $databaseTablesFromDatabase = $connection->createSchemaManager()->listTableNames();
        $databaseTablesFromSchema = $this->extractTableNamesFromSchemaDefinitions($this->collectionTableStatements($event));
        $mergedDatabaseTables = array_merge($databaseTablesFromDatabase, $databaseTablesFromSchema);
        $databaseTables = array_filter(array_unique($mergedDatabaseTables));

        foreach ($databaseTables as $tableName) {
            if ($this->shouldIgnoreTable($tableName)) {
                continue;
            }
            $sqlData[] = $this->createTableSchemaForTable($tableName);
        }
        $event->setSqlData($sqlData);
    }

    private function shouldIgnoreTable(string $tableName): bool
    {
        if (str_starts_with($tableName, 'cache_')) {
            return true;
        }

        return in_array($tableName, $this->ignoreTables, true);
    }

    private function createTableSchemaForTable(string $table): string
    {
        return <<<EOF
CREATE TABLE `{$table}` (
    seed_identifier VARCHAR(255) DEFAULT '' NOT NULL,
);
EOF;
    }

    /**
     * @param list<string> $schemaDefinitions
     * @return list<string>
     */
    private function extractTableNamesFromSchemaDefinitions(array $schemaDefinitions): array
    {
        $databaseTables = $this->coreTables;
        foreach ($schemaDefinitions as $schemaDefinitionMultipleLines) {
            $schemaDefinitionsForEachLine = GeneralUtility::trimExplode("\n", $schemaDefinitionMultipleLines);
            foreach ($schemaDefinitionsForEachLine as $schemaDefinition) {
                if (str_starts_with($schemaDefinition, '#')) {
                    continue;
                }
                if (!str_starts_with($schemaDefinition, 'CREATE') && !str_starts_with($schemaDefinition, 'ALTER')) {
                    continue;
                }
                preg_match_all('/(CREATE|ALTER) TABLE `?([a-z0-9_]+)`?/i', $schemaDefinition, $matches);
                if (empty($matches[2])) {
                    continue;
                }
                $databaseTables = array_merge($databaseTables, $matches[2]);
            }
        }
        return array_values(array_unique($databaseTables));
    }

    /**
     * @return list<string>
     */
    private function collectionTableStatements(AlterTableDefinitionStatementsEvent $event): array
    {
        $statements = array_values($event->getSqlData());
        $updateSuggestionsPerConnection = $this->schemaMigrator->getUpdateSuggestions([]);
        foreach ($updateSuggestionsPerConnection as $connectionName => $updateSuggestions) {
            unset($updateSuggestions['tables_count'], $updateSuggestions['change_currentValue']);
            $statements = array_merge($statements, ...array_values($updateSuggestions));
        }

        return array_values($statements);
    }
}
