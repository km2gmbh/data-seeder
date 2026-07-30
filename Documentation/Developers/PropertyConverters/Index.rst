.. include:: /Includes.rst.txt

..  _developers_property_converters:

===================
Property Converters
===================

Property converters are executed for each property of a node.
They are used to resolve references or to process relations.

Custom property converters can be registered by using the PHP attribute :bash:`\KM2\DataSeeder\Attribute\PropertyConverter`.

.. code-block:: php
  :caption: Custom property converter class example

  <?php

  namespace MyVendor\MyExtension\PropertyConverters;

  use KM2\DataSeeder\Attribute\PropertyConverter;
  use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
  use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConverterInterface;
  use KM2\DataSeeder\DataHandling\Property\Property
  use KM2\DataSeeder\DataHandling\RuntimeValues;

  #[PropertyConverter(identifier: 'replace-placeholder-property-converter', before: ['variable-property-converter'])]
  class MyCustomOperation implements PropertyConverterInterface
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

      public function convert(Property $property, NodeInterface $node): bool
      {
          $propertyValue = $property->getValue();
          if (!str_contains($propertyValue, '--placeholder--')) {
              return false;
          }

          $newPropertyValue = (string)str_replace('--placeholder--', 'Lorem ipsum', $propertyValue);
          $property->setValue($newPropertyValue);

          // When true is returned, property conversion is stopped for this property.
          return true;
      }
  }
