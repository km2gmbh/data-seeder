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

final readonly class RootNode implements NodeInterface
{
    public const string IDENTIFIER = 'root';
    public const string RECORD_TYPE = 'pages';

    private NodeCollection $childNodes;

    private PropertyCollection $properties;

    public function __construct()
    {
        $this->childNodes = new NodeCollection();
        $this->properties = new PropertyCollection(['uid' => 0]);
    }

    public function isProcessed(): bool
    {
        return true;
    }

    public function setProcessed(bool $isProcessed): void
    {
        // Not implemented. Root node cannot be processed.
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getChildNodes(): NodeCollection
    {
        return $this->childNodes;
    }

    public function getRecordType(): string
    {
        return self::RECORD_TYPE;
    }

    public function getProperties(): PropertyCollection
    {
        return $this->properties;
    }
}
