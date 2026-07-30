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
class RecordNode extends AbstractNode
{
    public function __construct(string $recordType, string $identifier, PropertyCollection $properties, NodeInterface $parentNode)
    {
        parent::__construct();
        $this->recordType = $recordType;
        $this->identifier = $identifier;
        $this->properties = $properties;
        $this->parentNode = $parentNode;
    }
}
