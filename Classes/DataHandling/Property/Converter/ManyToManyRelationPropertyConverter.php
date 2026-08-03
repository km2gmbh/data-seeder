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
use KM2\DataSeeder\DataHandling\Exception\InvalidConfigurationException;
use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\NodeFactory;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * @internal
 */
#[PropertyConverter(
    identifier: 'many-to-many-relation-property-converter',
    after: ['file-relation-property-converter']
)]
class ManyToManyRelationPropertyConverter extends AbstractPropertyConverter
{
    public function __construct(
        private readonly NodeResolver $nodeResolver,
        private readonly NodeFactory $nodeFactory,
        TcaSchemaFactory $tcaSchemaFactory,
    ) {
        $this->tcaSchemaFactory = $tcaSchemaFactory;
    }

    /**
     * @throws InvalidConfigurationException
     * @throws PropertyConversionException
     */
    public function convert(Property $property, NodeInterface $node): bool
    {
        $propertyName = $property->getName();
        $propertyValue = $property->getValue();

        $fieldType = $this->getTcaFieldTypeSchema($propertyName, $node);
        if (!$fieldType instanceof RelationalFieldTypeInterface || $fieldType->getRelationshipType() !== RelationshipType::ManyToMany) {
            return false;
        }

        $relation = $fieldType->getRelations()[0] ?? null;
        $MMTableName = $relation?->toTable() ?? null;
        if (empty($MMTableName)) {
            return false;
        }

        if (is_array($propertyValue) && isset($propertyValue['MM']['relations'])) {
            trigger_error(
                'Property "relations" is deprecated for MM records. Just add the records directly to MM property.',
                E_USER_DEPRECATED
            );
            $propertyValue = $propertyValue['MM']['relations'];
        }

        if (!is_array($propertyValue) || empty($propertyValue)) {
            return false;
        }

        // Add compatibility for older data that uses sys_category_record_mm in config.
        if (isset($propertyValue['sys_category_record_mm']) && is_array($propertyValue['sys_category_record_mm'])) {
            $propertyValue = array_map(static fn ($categoryData) => $categoryData['uid_local'], $propertyValue['sys_category_record_mm']);
        }

        if ($this->relationsContainOnlyReferences($propertyValue)) {
            $updatedPropertyValue = $this->handleRelationToExistingRecords(array_values($propertyValue), $MMTableName, $node, $fieldType);
            $property->setValue($updatedPropertyValue);

            return true;
        }

        if ($this->relationsContainOnlyNewRecords($propertyValue)) {
            $updatedPropertyValue = $this->handleRelationForNewRecords($propertyValue, $MMTableName, $node, $fieldType);
            $property->setValue($updatedPropertyValue);

            return true;
        }

        return false;
    }

    /**
     * @param list<string> $recordData
     * @throws PropertyConversionException
     */
    private function handleRelationToExistingRecords(
        array $recordData,
        string $MMTableName,
        NodeInterface $node,
        FieldTypeInterface $fieldType
    ): int {
        $sorting = 10;
        $tcaConfiguration = $fieldType->getConfiguration();
        foreach ($recordData as $relationCombinedIdentifier) {
            $combinedIdentifierDto = CombinedIdentifier::fromString($relationCombinedIdentifier);
            try {
                $relatedNode = $this->nodeResolver->resolve($combinedIdentifierDto);
            } catch (NodeUnresolvableException | MissingParentException $e) {
                throw new PropertyConversionException(
                    sprintf('Failed to convert property "uid" from node "%s:%s".', $combinedIdentifierDto->getRecordType(), $combinedIdentifierDto->getIdentifier()),
                    1783200454,
                    $e
                );
            }

            $localCombinedIdentifier = sprintf('%s:%s', $relatedNode->getRecordType(), $relatedNode->getIdentifier());
            $foreignCombinedIdentifier = sprintf('%s:%s', $node->getRecordType(), $node->getIdentifier());

            $MMProperties = array_replace(
                [
                    'uid_local' => isset($tcaConfiguration['MM_opposite_field']) ? $localCombinedIdentifier : $foreignCombinedIdentifier,
                    'uid_foreign' => isset($tcaConfiguration['MM_opposite_field']) ? $foreignCombinedIdentifier : $localCombinedIdentifier,
                    'sorting' => isset($tcaConfiguration['MM_opposite_field']) ? 0 : $sorting,
                    'sorting_foreign' => isset($tcaConfiguration['MM_opposite_field']) ? $sorting : 0,
                ],
                $tcaConfiguration['MM_match_fields'] ?? [],
            );
            $MMNode = $this->nodeFactory->buildMMNode($MMTableName, new PropertyCollection($MMProperties));
            $node->getChildNodes()->add($MMNode);

            $sorting += 10;
        }

        return count($recordData);
    }

    /**
     * @param array<string, array<string, mixed>> $newRecordData
     * @throws PropertyConversionException
     */
    private function handleRelationForNewRecords(
        array $newRecordData,
        string $MMTableName,
        NodeInterface $node,
        FieldTypeInterface $fieldType
    ): int {
        $sorting = 10;
        $tcaConfiguration = $fieldType->getConfiguration();
        $foreignField = $tcaConfiguration['foreign_field'] ?? null;
        $recordIdentifiers = [];

        // Create new record notes
        foreach ($newRecordData as $recordType => $records) {
            $sortingField = $this->getSortingFieldForRecordType($tcaConfiguration, $recordType);
            foreach ($records as $recordData) {
                $childRecordIdentifier = $recordData['identifier'] ?? null;
                if (empty($childRecordIdentifier)) {
                    throw new \RuntimeException('New records created by MM relation needs an identifier given', 1783533029);
                }
                if ($sortingField !== null && !isset($recordData[$sortingField])) {
                    $recordData[$sortingField] = $sorting;
                }
                if ($foreignField && !isset($recordData[$foreignField])) {
                    $recordData[$foreignField] = sprintf('%s:%s', $node->getRecordType(), $node->getIdentifier());
                }
                if (!isset($recordData['pid'])) {
                    $pidFromParent = $this->getPidFromNode($node);
                    if ($pidFromParent !== null) {
                        $recordData['pid'] = $pidFromParent;
                    }
                }
                $childNode = $this->nodeFactory->build($recordType, $childRecordIdentifier, new PropertyCollection($recordData), $node);
                $node->getChildNodes()->add($childNode);
                $recordIdentifiers[] = sprintf('%s:%s', $childNode->getRecordType(), $childNode->getIdentifier());
                $sorting += 10;
            }
        }

        return $this->handleRelationToExistingRecords($recordIdentifiers, $MMTableName, $node, $fieldType);
    }

    /**
     * @param array<string, mixed> $tcaConfiguration
     */
    private function getSortingFieldForRecordType(array $tcaConfiguration, string $recordType): ?string
    {
        if (!empty($tcaConfiguration['foreign_sortby'])) {
            return (string)$tcaConfiguration['foreign_sortby'];
        }

        return $GLOBALS['TCA'][$recordType]['ctrl']['sortby'] ?? null;
    }
}
