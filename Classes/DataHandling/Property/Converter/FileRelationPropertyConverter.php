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
use KM2\DataSeeder\DataHandling\Exception\InvalidConfigurationException;
use KM2\DataSeeder\DataHandling\Node\NodeFactory;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;

/**
 * @internal
 */
#[PropertyConverter(
    identifier: 'file-relation-property-converter',
    before: ['many-to-many-relation-property-converter', 'one-to-many-relation-property-converter'],
    after: ['relation-in-string-property-converter'],
)]
class FileRelationPropertyConverter extends AbstractPropertyConverter
{
    public function __construct(private readonly NodeFactory $nodeFactory)
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
        if ($fieldType?->getType() !== 'file') {
            return false;
        }

        if (!is_array($propertyValue) || empty($propertyValue)) {
            return false;
        }

        // Add compatibility to older versions that still has the reference table in config.
        if (isset($propertyValue['sys_file_reference']) && is_array($propertyValue['sys_file_reference'])) {
            $propertyValue = $propertyValue['sys_file_reference'];
        }

        $numberOfFileReferences = $this->handleFileRelations($propertyName, array_values($propertyValue), $node);
        $property->setValue($numberOfFileReferences);

        return true;
    }

    /**
     * @param list<array<string, mixed>> $recordData
     */
    private function handleFileRelations(string $propertyName, array $recordData, NodeInterface $node): int
    {
        $recordCount = 0;
        $sorting = 10;
        foreach ($recordData as $record) {
            $fileReferenceIdentifier = $record['identifier'] ?? null;
            $fileReferenceProperties = array_replace(
                [
                    'pid' => $this->getPidFromNode($node),
                    'uid_foreign' => sprintf('%s:%s', $node->getRecordType(), $node->getIdentifier()),
                    'tablenames' => $node->getRecordType(),
                    'fieldname' => $propertyName,
                    'sorting_foreign' => $sorting,
                ],
                $record
            );
            if (isset($fileReferenceProperties['file'])) {
                $fileIdentifier = $fileReferenceProperties['file'];
                if (!str_contains($fileIdentifier, ':')) {
                    $fileIdentifier = sprintf('sys_file:%s', $fileIdentifier);
                }
                $fileReferenceProperties['uid_local'] = $fileIdentifier;
                unset($fileReferenceProperties['file']);
            }

            $childNode = $this->nodeFactory->build(
                'sys_file_reference',
                $fileReferenceIdentifier,
                new PropertyCollection($fileReferenceProperties),
                $node
            );
            $node->getChildNodes()->add($childNode);
            $recordCount++;
            $sorting += 10;
        }

        return $recordCount;
    }
}
