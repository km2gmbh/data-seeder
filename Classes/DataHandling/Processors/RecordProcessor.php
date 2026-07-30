<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Processors;

use KM2\DataSeeder\Attribute\SeedingProcessor;
use KM2\DataSeeder\DataHandling\Node\MMNode;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConversionException;

/**
 * @internal
 */
#[SeedingProcessor(identifier: 'records', after: ['pages', 'sys_file'])]
class RecordProcessor extends AbstractRecordProcessor
{
    public function canProcess(NodeInterface $node): bool
    {
        return !in_array($node->getRecordType(), ['pages', 'sys_file']);
    }

    protected function processSingleRecord(NodeInterface $node): void
    {
        try {
            $this->convertProperties($node);
        } catch (PropertyConversionException) {
            // If some properties cannot be converted, create a minimal database record for current node.
            // If other node properties depends on this node, they can be resolved.
            // Node will not be marked as processed. The existing record will get updated next run.
            $this->createSkeletonRecord($node);
            return;
        }

        if ($this->recordExists($node)) {
            $this->updateRecord($node);
        } else {
            $this->createRecord($node);
        }

        $node->setProcessed(true);
    }

    protected function updateRecord(NodeInterface $node): void
    {
        $properties = $node->getProperties();
        $this->addTstampInformation($node->getRecordType(), $properties);

        $connection = $this->getConnectionForNode($node);
        $connection->update(
            $node->getRecordType(),
            $this->preparePropertiesForDatabaseStatement($properties),
            [
                'seed_identifier' => $node->getIdentifier(),
            ]
        );
    }

    protected function createRecord(NodeInterface $node): void
    {
        $properties = $node->getProperties();
        $properties->add('seed_identifier', $node->getIdentifier());
        $this->addSystemFieldsData($node->getRecordType(), $properties);

        $connection = $this->getConnectionForNode($node);
        $connection->insert($node->getRecordType(), $this->preparePropertiesForDatabaseStatement($properties));

        try {
            $recordUid = (int)$connection->lastInsertId();
        } catch (\Throwable) {
            // Exception is thrown when table has no primary key (like MM tables).
            return;
        }

        if ($recordUid > 0) {
            $node->getProperties()->add('uid', $recordUid);
        }
    }

    protected function createSkeletonRecord(NodeInterface $node): void
    {
        if ($node instanceof MMNode) {
            return;
        }

        $pid = 0;
        if ($node->getProperties()->has('pid')) {
            $pid = (int)$node->getProperties()->get('pid')->getValue();
        }

        $connection = $this->getConnectionForNode($node);
        $connection->insert(
            $node->getRecordType(),
            [
                'pid' => $pid,
                'seed_identifier' => $node->getIdentifier(),
            ]
        );

        try {
            $recordUid = (int)$connection->lastInsertId();
        } catch (\Throwable) {
            // Exception is thrown when table has no primary key (like MM tables).
            return;
        }

        if ($recordUid > 0) {
            $node->getProperties()->add('uid', $recordUid);
        }
    }

    protected function recordExists(NodeInterface $node): bool
    {
        $connection = $this->getConnectionForNode($node);

        return $connection->count(
            '*',
            $node->getRecordType(),
            [
                'seed_identifier' => $node->getIdentifier(),
            ]
        ) > 0;
    }
}
