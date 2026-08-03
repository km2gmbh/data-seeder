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
use KM2\DataSeeder\DataHandling\Node\PageNode;
use KM2\DataSeeder\DataHandling\RuntimeValues;
use TYPO3\CMS\Core\Schema\ActiveRelation;
use TYPO3\CMS\Core\Schema\Exception\UndefinedFieldException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
abstract class AbstractPropertyConverter implements PropertyConverterInterface
{
    protected Configuration $configuration;

    protected RuntimeValues $runtimeValues;

    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function setRuntimeValues(RuntimeValues $runtimeValues): void
    {
        $this->runtimeValues = $runtimeValues;
    }

    protected function getTcaFieldTypeSchema(string $columnName, NodeInterface $node): ?FieldTypeInterface
    {
        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);

        try {
            $tcaSchema = $tcaSchemaFactory->get($node->getRecordType());
        } catch (UndefinedSchemaException) {
            return null;
        }

        try {
            return $tcaSchema->getField($columnName);
        } catch (UndefinedFieldException) {
            return null;
        }
    }

    protected function getRelationForNode(RelationalFieldTypeInterface $fieldType, NodeInterface $node): ?ActiveRelation
    {
        return array_find($fieldType->getRelations(), fn ($relation) => $relation->toTable() === $node->getRecordType());
    }

    /**
     * @param array<int|string, mixed> $propertyValue
     */
    protected function relationsContainOnlyReferences(array $propertyValue): bool
    {
        $hasOnlyNumericKeys = array_all($propertyValue, static fn (mixed $value, mixed $key) => is_int($key));
        $hasOnlyStringValues = array_all($propertyValue, static fn (mixed $value, mixed $key) => is_string($value));

        return $hasOnlyNumericKeys && $hasOnlyStringValues;
    }

    /**
     * @param array<int|string, mixed> $propertyValue
     */
    protected function relationsContainOnlyNewRecords(array $propertyValue): bool
    {
        $hasOnlyStringKeys = array_all($propertyValue, static fn (mixed $value, mixed $key) => is_string($key));
        $hasOnlyArrayValues = array_all($propertyValue, static fn (mixed $value, mixed $key) => is_array($value));

        return $hasOnlyStringKeys && $hasOnlyArrayValues;
    }

    protected function getPidFromNode(NodeInterface $node): ?string
    {
        if ($node instanceof PageNode) {
            return sprintf('{%s:%s:uid}', $node->getRecordType(), $node->getIdentifier());
        }

        if (!$node->getProperties()->has('pid')) {
            return null;
        }

        return (string)$node->getProperties()->get('pid')->getValue();
    }
}
