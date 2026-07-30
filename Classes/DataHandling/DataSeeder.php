<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use KM2\DataSeeder\Configuration\ConfigurationPropertyException;
use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Exception\IncompleteNodePrecessingException;
use KM2\DataSeeder\DataHandling\Node\NodeTreeBuilder;
use KM2\DataSeeder\DataHandling\Node\RootNode;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

final class DataSeeder
{
    /**
     * @var array<string, array{before?: list<string>, after?: list<string>}>
     */
    private array $defaultOrdering = [
        'sys_file_storage' => [],
        'sys_file' => [
            'after' => ['sys_file_storage'],
        ],
        'pages' => [
            'after' =>  ['sys_file'],
        ],
        'sys_category' => [
            'after' => ['pages'],
        ],
        'tt_content' => [
            'after' => ['pages'],
        ],
    ];

    public function __construct(
        private readonly DependencyOrderingService $dependencyOrderingService,
        private readonly ProcessorManager $processorManager,
        private readonly NodeTreeBuilder $nodeTreeBuilder,
        private readonly ProgressIndicator $progressIndicator,
    ) {
    }

    /**
     * @throws ConfigurationPropertyException
     * @throws Node\MissingParentException
     * @throws IncompleteNodePrecessingException
     */
    public function seed(SeedingData $data, Configuration $configuration): RootNode
    {
        return $this->processData($data, $configuration);
    }

    /**
     * @throws Node\MissingParentException
     * @throws ConfigurationPropertyException
     * @throws IncompleteNodePrecessingException
     */
    private function processData(SeedingData $data, Configuration $configuration): RootNode
    {
        $orderedRecords = $this->buildOrderedRecordsArray($data->getSeedingData(), $configuration);
        $orderedRecordTypes = array_keys($orderedRecords);

        $this->progressIndicator->start('Processing seeds');

        $rootNode = $this->nodeTreeBuilder->build($orderedRecordTypes, $data->getSeedingData(), $configuration);

        $runtimeValues = new RuntimeValues($data->getVariables());

        $processingCyclesLeft = 3;
        do {
            $isFullyProcessed = $this->processorManager->processNode($rootNode, $configuration, $runtimeValues);
            $processingCyclesLeft--;
        } while (!$isFullyProcessed && $processingCyclesLeft > 0);

        if (!$isFullyProcessed) {
            $exception = new IncompleteNodePrecessingException('Failed importing all data.', 1784222615);
            $exception->setNode($rootNode);
            throw $exception;
        }

        $this->progressIndicator->finish('All seeds processed');

        return $rootNode;
    }

    /**
     * @param array<string, mixed> $records
     * @return array<string, mixed>
     */
    private function buildOrderedRecordsArray(array $records, Configuration $configuration): array
    {
        array_walk($records, static fn (&$item) => $item = []);
        $recordOrdering = array_replace_recursive($this->defaultOrdering, $configuration->getOrdering());
        $mergedRecords = array_merge(
            $records,
            $recordOrdering
        );

        return $this->dependencyOrderingService->orderByDependencies($mergedRecords);
    }
}
