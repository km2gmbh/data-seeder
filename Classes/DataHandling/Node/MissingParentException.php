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

final class MissingParentException extends \Exception
{
    private PropertyCollection $nodeProperties;

    public function getNodeProperties(): PropertyCollection
    {
        return $this->nodeProperties;
    }

    public function setNodeProperties(PropertyCollection $nodeProperties): void
    {
        $this->nodeProperties = $nodeProperties;
    }
}
