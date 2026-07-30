<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Node;

use KM2\DataSeeder\DataHandling\CombinedIdentifier;
use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Exception\RecordNotFoundException;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use KM2\DataSeeder\Event\InitializeRecordNodeEvent;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

#[Autoconfigure(public: true)]
final readonly class NodeFactory
{
    public function __construct(
        private EventDispatcher $eventDispatcher,
        private NodeResolver $nodeResolver,
    ) {
    }

    /**
     * @throws MissingParentException
     */
    public function build(string $recordType, ?string $identifier, PropertyCollection $properties, ?NodeInterface $parentNode = null): NodeInterface
    {
        if (!is_string($identifier)) {
            if ($properties->has('identifier')) {
                $identifier = (string)$properties->get('identifier')->getValue();
            } else {
                $identifier = $this->generateIdentifier($recordType, $properties);
            }
        }

        $properties->remove('identifier');

        $combinedIdentifier = new CombinedIdentifier($recordType, $identifier);
        $existingProperties = $this->getExitingProperties($combinedIdentifier);
        $existingProperties->merge($properties);

        $parentNode ??= $this->buildParentNode($recordType, $existingProperties);
        $event = new InitializeRecordNodeEvent($recordType, $identifier, $existingProperties, $parentNode);
        $this->eventDispatcher->dispatch($event);

        $node = $event->getNode();
        if (!$node instanceof NodeInterface) {
            $node = match ($recordType) {
                'pages', 'page' => new PageNode($identifier, $existingProperties, $parentNode),
                'sys_file', 'files' => new FileNode($identifier, $existingProperties, $parentNode),
                'sys_file_storage' => new FalStorageNode($identifier, $properties),
                default => new RecordNode($recordType, $identifier, $existingProperties, $parentNode),
            };
        }

        $this->nodeResolver->getCachedNodes()->add($node);

        return $node;
    }

    /**
     * Todo Check if there is a way to check if record is MMNode and move building in build() method.
     */
    public function buildMMNode(string $recordType, PropertyCollection $properties): MMNode
    {
        $identifier = $this->generateIdentifier($recordType, $properties);
        $combinedIdentifier = new CombinedIdentifier($recordType, $identifier);
        $existingProperties = $this->getExitingProperties($combinedIdentifier);
        $existingProperties->merge($properties);

        $event = new InitializeRecordNodeEvent($recordType, $identifier, $existingProperties);
        $this->eventDispatcher->dispatch($event);

        $node = $event->getNode();
        if (!$node instanceof MMNode) {
            $node = new MMNode($recordType, $identifier, $properties);
        }

        $this->nodeResolver->getCachedNodes()->add($node);

        return $node;
    }

    public function buildRootNode(): RootNode
    {
        $combinedIdentifier = new CombinedIdentifier(RootNode::RECORD_TYPE, RootNode::IDENTIFIER);
        /** @var RootNode $rootNode */
        $rootNode = $this->nodeResolver->resolve($combinedIdentifier);

        return $rootNode;
    }

    /**
     * @throws MissingParentException
     */
    private function buildParentNode(string $recordType, PropertyCollection $properties): NodeInterface
    {
        $parentCombinedIdentifier = 'pages:root';
        if ($properties->has('pid')) {
            $parentCombinedIdentifier = $properties->get('pid')->getValue();
        }

        if ($parentCombinedIdentifier === 0 || $parentCombinedIdentifier === '0') {
            // Compat for older versions.
            $parentCombinedIdentifier = 'pages:root';
        }
        if (!is_string($parentCombinedIdentifier) || empty($parentCombinedIdentifier)) {
            $exception = new MissingParentException(
                sprintf('Missing or empty pid for record of type %s.', $recordType),
                1783098784
            );
            $exception->setNodeProperties($properties);
            throw $exception;
        }

        $parentCombinedIdentifierDto = CombinedIdentifier::fromString($parentCombinedIdentifier);
        try {
            return $this->nodeResolver->resolve($parentCombinedIdentifierDto);
        } catch (NodeUnresolvableException $e) {
            $exception = new MissingParentException(
                sprintf('Unable to resolve parent record with identifier "%s".', $parentCombinedIdentifier),
                1783110126,
                $e
            );
            $exception->setNodeProperties($properties);
            throw $exception;
        }
    }

    private function generateIdentifier(string $recordType, PropertyCollection $properties): string
    {
        $jsonProperties = json_encode($properties);
        if ($jsonProperties !== false) {
            return md5($recordType . $jsonProperties);
        }

        $length = 24;
        $triesLeft = 5;
        $generatedIdentifier = '';
        while ($triesLeft > 0 && strlen($generatedIdentifier) === 0) {
            try {
                /** @var int<1, max> $lengthForGeneration */
                $lengthForGeneration = (int)($length / 2);
                $generatedIdentifier = bin2hex(random_bytes($lengthForGeneration));
            } catch (\Exception) {
                $triesLeft--;
            }
        }

        if (strlen($generatedIdentifier) === 0) {
            throw new \RuntimeException('Could not generate random password.', 1783196201);
        }

        return $generatedIdentifier;
    }

    private function getExitingProperties(CombinedIdentifier $combinedIdentifier): PropertyCollection
    {
        $properties = new PropertyCollection();

        try {
            $recordProperties = $this->nodeResolver->getPropertiesByCombinedIdentifier($combinedIdentifier);
        } catch (RecordNotFoundException) {
            return $properties;
        }

        foreach ($recordProperties as $propertyName => $propertyValue) {
            // Skip empty properties to speed up property conversion later on.
            // It is assumed that empty properties corresponds to database default values.
            if (!empty($propertyValue)) {
                $properties->add($propertyName, $propertyValue);
            }
        }

        return $properties;
    }
}
