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
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;

final class InitializeRecordNodeEvent
{
    private ?NodeInterface $node = null;

    public function __construct(
        private readonly string $recordType,
        private readonly ?string $identifier,
        private readonly PropertyCollection $properties,
        private readonly ?NodeInterface $parentNode = null,
    ) {
    }

    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    public function setNode(NodeInterface $node): void
    {
        $this->node = $node;
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getProperties(): PropertyCollection
    {
        return $this->properties;
    }

    public function getParentNode(): ?NodeInterface
    {
        return $this->parentNode;
    }
}
