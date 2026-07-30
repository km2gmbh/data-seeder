<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Node;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use KM2\DataSeeder\Event\BeforeNodeBuildingEvent;
use KM2\DataSeeder\Repository\StorageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

final readonly class NodeTreeBuilder
{
    public function __construct(
        private NodeFactory $nodeFactory,
        private EventDispatcher $eventDispatcher,
        private StorageRepository $storageRepository,
    ) {
    }

    /**
     * @param list<string> $orderedRecordTypes
     * @param array<string, list<array<string, mixed>>> $seedingData
     * @throws MissingParentException
     */
    public function build(array $orderedRecordTypes, array $seedingData, Configuration $configuration): RootNode
    {
        $rootNode = $this->nodeFactory->buildRootNode();

        $this->createStorageNodes($configuration);

        foreach ($orderedRecordTypes as $recordType) {
            $records = $seedingData[$recordType] ?? [];

            $event = new BeforeNodeBuildingEvent($recordType, $records);
            $this->eventDispatcher->dispatch($event);

            foreach ($event->getRecords() as $record) {
                $identifier = $record['identifier'] ?? null;
                if ($identifier !== null) {
                    $identifier = (string)$identifier;
                }
                $node = $this->nodeFactory->build($recordType, $identifier, new PropertyCollection($record));
                if ($node instanceof NodeHasParentNodeInterface) {
                    $node->getParentNode()->getChildNodes()->add($node);
                }
            }
        }

        return $rootNode;
    }

    private function createStorageNodes(Configuration $configuration): void
    {
        foreach ($configuration->getStorages() as $storage) {
            if ($this->storageRepository->hasResourceStorage($storage->getIdentifier())) {
                $storageUid = $this->storageRepository->getResourceStorageUid($storage->getIdentifier());
            } else {
                $storageUid =  $this->storageRepository->createLocalResourceStorage($storage, 'Created by data seeder');
            }
            $properties = new PropertyCollection(['uid' => $storageUid, 'pid' => 'pages:root']);
            $this->nodeFactory->build('sys_file_storage', $storage->getIdentifier(), $properties);
        }
    }
}
