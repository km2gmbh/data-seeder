<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Event;

use KM2\DataSeeder\DataHandling\Node\NodeInterface;

final class BeforeProcessingNodeEvent
{
    public function __construct(private NodeInterface $node)
    {
    }

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }

    public function getNode(): NodeInterface
    {
        return $this->node;
    }
}
