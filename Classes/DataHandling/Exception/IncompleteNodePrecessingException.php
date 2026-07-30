<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Exception;

use KM2\DataSeeder\DataHandling\Node\NodeInterface;

class IncompleteNodePrecessingException extends \Exception
{
    private NodeInterface $node;

    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    /**
     * @return list<NodeInterface>
     */
    public function getUnprocessedNodes(): array
    {
        return $this->extractUnprocessedNodes($this->node);
    }

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }

    /**
     * @return list<NodeInterface>
     */
    private function extractUnprocessedNodes(NodeInterface $node): array
    {
        $unprocessedNodes = [];
        if (!$node->isProcessed()) {
            $unprocessedNodes[] = $node;
        }

        foreach ($node->getChildNodes()->getAll() as $groupedChildNodes) {
            foreach ($groupedChildNodes as $childNode) {
                $unprocessedNodes = array_merge($unprocessedNodes, $this->extractUnprocessedNodes($childNode));
            }
        }

        return $unprocessedNodes;
    }
}
