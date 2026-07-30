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
use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\Property;

/**
 * @internal
 */
#[PropertyConverter(identifier: 'relation-in-string-property-converter', after: ['single-node-relation-property-converter'])]
class RelationInStringPropertyConverter extends AbstractPropertyConverter
{
    public function __construct(private readonly NodeResolver $nodeResolver)
    {
    }

    public function convert(Property $property, NodeInterface $node): bool
    {
        $propertyValue = $property->getValue();
        if (!is_string($propertyValue)) {
            return false;
        }

        preg_match_all('/{[a-zA-Z0-9_]+:[a-zA-Z0-9_\-]+(:[a-zA-Z0-9_\-]+)?}/', $propertyValue, $matches);
        if (!isset($matches[0][0])) {
            return false;
        }

        $replacements = [];
        foreach ($matches[0] as $match) {
            $combinedIdentifierDto = CombinedIdentifier::fromString($match);
            try {
                $resolvedNode = $this->nodeResolver->resolve($combinedIdentifierDto);
                $resolvedValue = $resolvedNode->getProperties()->get($combinedIdentifierDto->getProperty());
                $replacements[$match] = $resolvedValue->getValue();
            } catch (NodeUnresolvableException | MissingParentException | InvalidPropertyException $e) {
                throw new PropertyConversionException(
                    sprintf(
                        'Failed to convert property "%s" from node "%s:%s".',
                        $combinedIdentifierDto->getProperty(),
                        $combinedIdentifierDto->getRecordType(),
                        $combinedIdentifierDto->getIdentifier()
                    ),
                    1783200281,
                    $e
                );
            }
        }

        $convertedPropertyValue = str_replace(array_keys($replacements), array_values($replacements), $propertyValue);
        $property->setValue($convertedPropertyValue);

        return true;
    }
}
