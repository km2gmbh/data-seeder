<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Property\Converter;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\RuntimeValues;

interface PropertyConverterInterface
{
    /**
     * @return bool Returns true if the property was converted by this converter. Otherwise false.
     * @throws PropertyConversionException
     */
    public function convert(Property $property, NodeInterface $node): bool;

    public function setConfiguration(Configuration $configuration): void;

    public function setRuntimeValues(RuntimeValues $runtimeValues): void;
}
