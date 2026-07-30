<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Node;

final class NodeCollection
{
    /**
     * @var array<string, array<string, NodeInterface>>
     */
    private array $nodes = [];

    public function add(NodeInterface $node): void
    {
        $recordType = $node->getRecordType();
        if (!isset($this->nodes[$recordType])) {
            $this->nodes[$recordType] = [];
        }
        $this->nodes[$recordType][$node->getIdentifier()] = $node;
    }

    /**
     * @return array<string, NodeInterface>
     */
    public function get(string $recordType): array
    {
        return $this->nodes[$recordType] ?? [];
    }

    /**
     * @return array<string, array<string, NodeInterface>>
     */
    public function getAll(): array
    {
        return $this->nodes;
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }
}
