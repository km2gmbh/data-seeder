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

final class MMNode implements NodeInterface
{
    private bool $isProcessed = false;

    public function __construct(
        private readonly string $recordType,
        private readonly string $identifier,
        private readonly PropertyCollection $properties
    ) {
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

    public function getChildNodes(): NodeCollection
    {
        return new NodeCollection();
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }
}
