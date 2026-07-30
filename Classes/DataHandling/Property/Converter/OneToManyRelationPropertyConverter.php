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
use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\NodeFactory;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;

/**
 * @internal
 */
#[PropertyConverter(
    identifier: 'one-to-many-relation-property-converter',
    after: ['file-relation-property-converter']
)]
class OneToManyRelationPropertyConverter extends AbstractPropertyConverter
{
    public function __construct(private readonly NodeResolver $nodeResolver, private readonly NodeFactory $nodeFactory)
    {
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
        if (!$fieldType instanceof RelationalFieldTypeInterface || $fieldType->getRelationshipType() === RelationshipType::ManyToMany) {
            return false;
        }

        if (!is_array($propertyValue) || empty($propertyValue)) {
            return false;
        }

        if ($this->relationsContainOnlyReferences($propertyValue)) {
            $updatedPropertyValue = $this->handleExistingRecordRelations($propertyName, array_values($propertyValue), $node);
            $property->setValue($updatedPropertyValue);

            return true;
        }

        if ($this->relationsContainOnlyNewRecords($propertyValue)) {
            $updatedPropertyValue = $this->handleNewRecordRelations($propertyName, $propertyValue, $node);
            $property->setValue($updatedPropertyValue);

            return true;
        }

        return false;
    }

    /**
     * @param list<string> $recordData
     * @throws PropertyConversionException
     */
    private function handleExistingRecordRelations(
        string $propertyName,
        array $recordData,
        NodeInterface $node
    ): string {
        $recordUids = [];
        /** @var FieldTypeInterface&RelationalFieldTypeInterface $fieldType */
        $fieldType = $this->getTcaFieldTypeSchema($propertyName, $node);
        $relation = $this->getRelationForNode($fieldType, $node);
        $foreignField = $relation?->toField();

        if ($foreignField !== null) {
            throw new \RuntimeException(
                sprintf(implode(' ', [
                    'Relations for existing records having a foreign_field defined in TCA are not supported, yet.',
                    'Thrown by recordType "%s" and property "%s".'
                ]), $node->getRecordType(), $propertyName),
                1783724770
            );
        }

        foreach ($recordData as $combinedIdentifier) {
            $combinedIdentifierDto = CombinedIdentifier::fromString($combinedIdentifier);
            try {
                $relatedNode = $this->nodeResolver->resolve($combinedIdentifierDto);
                $recordUids[] = (int)$relatedNode->getProperties()->get('uid')->getValue();
            } catch (NodeUnresolvableException | MissingParentException | InvalidPropertyException $e) {
                throw new PropertyConversionException(
                    sprintf('Failed to convert property "uid" from node "%s:%s".', $combinedIdentifierDto->getRecordType(), $combinedIdentifierDto->getIdentifier()),
                    1783200454,
                    $e
                );
            }
        }

        return implode(',', $recordUids);
    }

    /**
     * This method should create new IRRE relations.
     * Structure for $recordData
     * - <table>
     *     - record_1_field: <value>
     *       record_1_another_field: <value>
     *     - record_2_field: <value>
     *       record_2_another_field: <value>
     *
     * @param array<string, array<string, mixed>> $recordData
     */
    private function handleNewRecordRelations(
        string $propertyName,
        array $recordData,
        NodeInterface $node
    ): int|string {
        /** @var FieldTypeInterface&RelationalFieldTypeInterface $fieldType */
        $fieldType = $this->getTcaFieldTypeSchema($propertyName, $node);
        $foreignField = null;

        $recordIdentifiers = [];
        $recordCount = 0;
        $sorting = 10;
        foreach ($recordData as $recordType => $childRecords) {
            $sortingField = $this->getSortingFieldForRecordType($fieldType, $recordType);
            $relation = array_find($fieldType->getRelations(), fn ($relation) => $relation->toTable() === $recordType);
            $foreignField = $relation?->toField();

            foreach ($childRecords as $childRecordData) {
                $childRecordIdentifier = $childRecordData['identifier'] ?? null;
                if ($sortingField !== null && !isset($childRecordData[$sortingField])) {
                    $childRecordData[$sortingField] = $sorting;
                }
                if ($foreignField && !isset($childRecordData[$foreignField])) {
                    $childRecordData[$foreignField] = sprintf('%s:%s', $node->getRecordType(), $node->getIdentifier());
                }
                if (!isset($childRecordData['pid'])) {
                    $pidFromParent = $this->getPidFromNode($node);
                    if ($pidFromParent !== null) {
                        $childRecordData['pid'] = $pidFromParent;
                    }
                }
                $childNode = $this->nodeFactory->build($recordType, $childRecordIdentifier, new PropertyCollection($childRecordData), $node);
                $recordIdentifiers[] = sprintf('%s:%s', $childNode->getRecordType(), $childNode->getIdentifier());
                $node->getChildNodes()->add($childNode);
                $recordCount++;
                $sorting += 10;
            }
        }

        return $foreignField === null ? implode(',', $recordIdentifiers) :  $recordCount;
    }

    private function getSortingFieldForRecordType(FieldTypeInterface $fieldType, string $recordType): ?string
    {
        if (!empty($fieldType->getConfiguration()['foreign_sortby'])) {
            return (string)$fieldType->getConfiguration()['foreign_sortby'];
        }

        return $GLOBALS['TCA'][$recordType]['ctrl']['sortby'] ?? null;
    }
}
