<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Node;

use KM2\DataSeeder\DataHandling\Property\PropertyCollection;

/**
 * @internal
 */
abstract class AbstractNode implements NodeInterface, NodeHasParentNodeInterface
{
    protected string $identifier;

    protected PropertyCollection $properties;

    protected NodeInterface $parentNode;

    protected NodeCollection $childNodes;

    protected string $recordType;

    protected bool $isProcessed = false;

    public function __construct()
    {
        $this->childNodes = new NodeCollection();
        $this->properties = new PropertyCollection();
    }

    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }

    public function setProcessed(bool $isProcessed): void
    {
        $this->isProcessed = $isProcessed;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getProperties(): PropertyCollection
    {
        return $this->properties;
    }

    public function getParentNode(): NodeInterface
    {
        return $this->parentNode;
    }

    public function getChildNodes(): NodeCollection
    {
        return $this->childNodes;
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }
}
