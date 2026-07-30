<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Processors;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Exception\MissingIdentifierException;
use KM2\DataSeeder\DataHandling\Exception\MissingPropertyException;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConversionException;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use KM2\DataSeeder\DataHandling\Property\PropertyConverter;
use KM2\DataSeeder\DataHandling\RuntimeValues;
use KM2\DataSeeder\Event\BeforeProcessingNodeEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
abstract class AbstractRecordProcessor implements ProcessorInterface
{
    private PropertyConverter $propertyConverter;

    private EventDispatcher $eventDispatcher;

    /**
     * Property "identifier" is always required.
     *
     * @var list<string>
     */
    protected array $requiredProperties = [];

    protected Configuration $configuration;

    protected RuntimeValues $runtimeValues;

    /**
     * @throws MissingIdentifierException
     * @throws MissingPropertyException
     */
    #[\Override]
    public function process(NodeInterface $node): void
    {
        $this->validateData($node);

        $beforeProcessingEvent = new BeforeProcessingNodeEvent($node);
        $this->getEventDispatcher()->dispatch($beforeProcessingEvent);

        $node = $beforeProcessingEvent->getNode();
        $this->processSingleRecord($node);
    }

    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function setRuntimeValues(RuntimeValues $runtimeValues): void
    {
        $this->runtimeValues = $runtimeValues;
    }

    abstract protected function processSingleRecord(NodeInterface $node): void;

    /**
     * @throws PropertyConversionException
     */
    protected function convertProperties(NodeInterface $node): void
    {
        $this->getPropertyConverter()->convertPropertiesForNode($node);
    }

    /**
     * @throws MissingIdentifierException
     * @throws MissingPropertyException
     */
    protected function validateData(NodeInterface $node): void
    {
        $recordType = $node->getRecordType();
        if (empty($recordType)) {
            throw new MissingIdentifierException(
                sprintf('Record type for node "%s" is empty.', $node->getIdentifier()),
                1736247679
            );
        }

        foreach ($this->requiredProperties as $requiredProperty) {
            if (!$node->getProperties()->has($requiredProperty)) {
                throw new MissingPropertyException($requiredProperty, $recordType, 1736248598);
            }
        }
    }

    protected function addSystemFieldsData(string $table, PropertyCollection $properties): void
    {
        $creationDateField = $GLOBALS['TCA'][$table]['ctrl']['crdate'] ?? null;
        if (is_string($creationDateField) && !empty($creationDateField) && !$properties->has($creationDateField)) {
            $properties->add($creationDateField, new \DateTimeImmutable()->getTimestamp());
        }

        $deletedField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        if (is_string($deletedField) && !empty($deletedField) && !$properties->has($deletedField)) {
            $properties->add($deletedField, 0);
        }

        $disabledField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? null;
        if (is_string($disabledField) && !empty($disabledField) && !$properties->has($disabledField)) {
            $properties->add($disabledField, 0);
        }

        $this->addTstampInformation($table, $properties);
    }

    protected function addTstampInformation(string $table, PropertyCollection $properties): void
    {
        $updateDateField = $GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? null;
        if (is_string($updateDateField) && !empty($updateDateField) && !$properties->has($updateDateField)) {
            $properties->add($updateDateField, new \DateTimeImmutable()->getTimestamp());
        }
    }

    private function getPropertyConverter(): PropertyConverter
    {
        if (!isset($this->propertyConverter)) {
            $this->propertyConverter = GeneralUtility::makeInstance(PropertyConverter::class);
            $this->propertyConverter->setConfiguration($this->configuration);
            $this->propertyConverter->setRuntimeValues($this->runtimeValues);
        }
        return $this->propertyConverter;
    }

    protected function getEventDispatcher(): EventDispatcher
    {
        if (!isset($this->eventDispatcher)) {
            $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        }
        return $this->eventDispatcher;
    }

    protected function getConnectionForNode(NodeInterface $node): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($node->getRecordType());
    }

    /**
     * @return array<string, mixed>
     */
    protected function preparePropertiesForDatabaseStatement(PropertyCollection $properties): array
    {
        $preparedProperties = [];
        $properties->rewind();
        foreach ($properties as $property) {
            $preparedProperties[$property->getName()] = $property->getValue();
        }

        return $preparedProperties;
    }
}
