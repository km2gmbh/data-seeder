<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Property;

use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;

/**
 * @implements \Iterator<int, Property>
 */
final class PropertyCollection implements \Iterator, \Countable, \JsonSerializable
{
    private int $position = 0;

    /**
     * @var array<string, Property>
     */
    private array $properties = [];

    /**
     * @var list<string>
     */
    private array $propertyNames = [];

    /**
     * @param array<string, mixed>|list<Property> $properties
     */
    public function __construct(array $properties = [])
    {
        $this->set($properties);
    }

    /**
     * @param array<string, mixed>|list<Property> $properties
     */
    public function set(array $properties): void
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if ($propertyValue instanceof Property) {
                $this->properties[$propertyValue->getName()] = $propertyValue;
                continue;
            }

            if (!is_string($propertyName)) {
                throw new \RuntimeException(
                    sprintf('Array key for $properties needs to be a string or value need to be of type %s.', Property::class),
                    1783246641
                );
            }

            $this->properties[$propertyName] = new Property($propertyName, $propertyValue);
        }

        $this->propertyNames = array_keys($this->properties);
    }

    public function add(string $propertyName, mixed $propertyValue): void
    {
        $this->properties[$propertyName] = new Property($propertyName, $propertyValue);
        $this->propertyNames = array_keys($this->properties);
    }

    /**
     * @throws InvalidPropertyException
     */
    public function get(string $propertyName): Property
    {
        if (!$this->has($propertyName)) {
            throw new InvalidPropertyException(
                sprintf('Property %s does not exist.', $propertyName),
                1782643494
            );
        }

        return $this->properties[$propertyName];
    }

    public function has(string $propertyName): bool
    {
        return isset($this->properties[$propertyName]);
    }

    public function merge(PropertyCollection $collection): void
    {
        foreach ($collection as $property) {
            $this->properties[$property->getName()] = $property;
        }
        $this->propertyNames = array_keys($this->properties);
    }

    public function remove(string $propertyName): void
    {
        if ($this->has($propertyName)) {
            unset($this->properties[$propertyName]);
        }
        $this->propertyNames = array_keys($this->properties);
    }

    public function current(): Property
    {
        $propertyName = $this->propertyNames[$this->position];

        return $this->properties[$propertyName];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->propertyNames[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->properties);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->properties as $property) {
            $result[$property->getName()] = $property->getValue();
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(static fn (Property $property) => $property->getValue(), $this->properties);
    }
}
