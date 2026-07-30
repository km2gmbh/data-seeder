<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Exception\RecordNotFoundException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\NodeCollection;
use KM2\DataSeeder\DataHandling\Node\NodeFactory;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Node\RootNode;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NodeResolver implements SingletonInterface
{
    private NodeCollection $nodes;

    public function __construct(private ConnectionPool $connectionPool)
    {
        $this->nodes = new NodeCollection();
        $this->nodes->add(new RootNode());
    }

    public function getCachedNodes(): NodeCollection
    {
        return $this->nodes;
    }

    /**
     * @throws NodeUnresolvableException
     * @throws MissingParentException
     */
    public function resolve(CombinedIdentifier $combinedIdentifier): NodeInterface
    {
        $node = $this->nodes->get($combinedIdentifier->getRecordType())[$combinedIdentifier->getIdentifier()] ?? null;
        if ($node instanceof NodeInterface) {
            return $node;
        }

        try {
            $recordData = $this->getPropertiesByCombinedIdentifier($combinedIdentifier);
        } catch (RecordNotFoundException) {
            throw new NodeUnresolvableException(
                sprintf('Node for combined identifier "%s:%s" cannot be resolved.', $combinedIdentifier->getRecordType(), $combinedIdentifier->getIdentifier()),
                1783161214
            );
        }

        return GeneralUtility::makeInstance(NodeFactory::class)->build(
            $combinedIdentifier->getRecordType(),
            $combinedIdentifier->getIdentifier(),
            new PropertyCollection($recordData)
        );
    }

    /**
     * @return array<string, mixed>
     * @throws RecordNotFoundException
     */
    public function getPropertiesByCombinedIdentifier(CombinedIdentifier $combinedIdentifier): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($combinedIdentifier->getRecordType());
        $queryBuilder->getRestrictions()->removeAll();

        $recordData = $queryBuilder
            ->select('*')
            ->from($combinedIdentifier->getRecordType())
            ->where(
                $queryBuilder->expr()->eq('seed_identifier', $queryBuilder->createNamedParameter($combinedIdentifier->getIdentifier())),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($recordData === false) {
            throw new RecordNotFoundException(
                sprintf('No record with identifier "%s" in table %s found.', $combinedIdentifier->getIdentifier(), $combinedIdentifier->getRecordType()),
                1783108646
            );
        }

        return $recordData;
    }
}
