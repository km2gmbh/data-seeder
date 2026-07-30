.. include:: /Includes.rst.txt

..  _developers_processors:

==========
Processors
==========

Processors are used to process single nodes and write data to the database.
By default, this extension ships three processors:

- :bash:`\KM2\DataSeeder\DataHandling\Processors\FilesProcessor` (sys_file)
- :bash:`\KM2\DataSeeder\DataHandling\Processors\PagesProcessor` (pages)
- :bash:`\KM2\DataSeeder\DataHandling\Processors\RecordProcessor` (records)

These processors are checked and executed in displayed order for each node.

New processors can be registered by using the PHP attribute :bash:`\KM2\DataSeeder\Attribute\SeedingProcessor`.

.. code-block:: php
  :caption: Custom processor class example

  <?php

  namespace MyVendor\MyExtension\Processors;

  use KM2\DataSeeder\Attribute\SeedingProcessor;
  use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
  use KM2\DataSeeder\DataHandling\Processors\ProcessorInterface;
  use KM2\DataSeeder\DataHandling\RuntimeValues;

  #[SeedingProcessor(identifier: 'categoryProcessor', before: ['records'], after: ['pages', 'sys_file'])]
  class MyCustomOperation implements ProcessorInterface
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

      public function canProcess(NodeInterface $node): bool
      {
          return $node->getRecordType() === 'sys_category';
      }

      public function process(NodeInterface $node): void
      {
          // Write node properties to database.

          // Mark node as processed to skip further processing.
          $node->setProcessed(true);
      }
  }
