<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Property\Converter;

use Doctrine\DBAL\Exception\TableNotFoundException;
use KM2\DataSeeder\Attribute\PropertyConverter;
use KM2\DataSeeder\DataHandling\CombinedIdentifier;
use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\Property;

/**
 * Convert property value if it's only an identifier (direct match).
 *
 * @internal
 */
#[PropertyConverter('single-node-relation-property-converter')]
class SingleNodeRelationPropertyConverter extends AbstractPropertyConverter
{
    public function __construct(private readonly NodeResolver $nodeResolver)
    {
    }

    /**
     * @throws PropertyConversionException
     */
    public function convert(Property $property, NodeInterface $node): bool
    {
        $propertyValue = $property->getValue();
        if (!is_string($propertyValue) || !CombinedIdentifier::isValid($propertyValue)) {
            return false;
        }

        $combinedIdentifierDto = CombinedIdentifier::fromString($propertyValue);
        try {
            $resolvedNode = $this->nodeResolver->resolve($combinedIdentifierDto);
            $property->setValue($resolvedNode->getProperties()->get($combinedIdentifierDto->getProperty())->getValue());
        } catch (NodeUnresolvableException | MissingParentException | InvalidPropertyException $e) {
            throw new PropertyConversionException(
                sprintf('Failed to convert property "%s" from node "%s:%s".', $property->getName(), $node->getRecordType(), $node->getIdentifier()),
                1783199930,
                $e
            );
        } catch (TableNotFoundException) {
            // Value mit be something aaa:bbb and is a valid string.
            return true;
        }

        return true;
    }
}
