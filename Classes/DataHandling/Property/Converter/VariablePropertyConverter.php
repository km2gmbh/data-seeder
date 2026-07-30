<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Property\Converter;

use KM2\DataSeeder\Attribute\PropertyConverter;
use KM2\DataSeeder\DataHandling\CombinedIdentifier;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Property;

/**
 * Convert property value if it's only an identifier (direct match).
 *
 * @internal
 */
#[PropertyConverter('variable-property-converter', before: ['single-node-relation-property-converter'])]
class VariablePropertyConverter extends AbstractPropertyConverter
{
    /**
     * Always returns false to allow other converters use the new value.
     */
    public function convert(Property $property, NodeInterface $node): bool
    {
        $propertyValue = $property->getValue();
        if (!is_string($propertyValue)) {
            return false;
        }

        preg_match_all('/{variable:[a-zA-Z0-9_\-]+}/', $propertyValue, $matches);
        if (!isset($matches[0][0])) {
            return false;
        }

        $replacements = [];
        foreach ($matches[0] as $match) {
            $variableName = trim($match, '{}');
            $combinedIdentifierDto = CombinedIdentifier::fromString($variableName);
            if (!$this->runtimeValues->getVariables()->has($combinedIdentifierDto->getIdentifier())) {
                continue;
            }
            $replacements[$match] = (string)$this->runtimeValues->getVariables()->get($combinedIdentifierDto->getIdentifier())->getValue();
        }

        $convertedPropertyValue = str_replace(array_keys($replacements), array_values($replacements), $propertyValue);
        $property->setValue($convertedPropertyValue);

        return false;
    }
}
