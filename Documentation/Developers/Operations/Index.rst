.. include:: /Includes.rst.txt

..  _developers_operations:

==========
Operations
==========

See :ref:`Operations <_configuration_operations>` for explanation.

Custom operations can be registered using the PHP attribute :bash:`\KM2\DataSeeder\Attribute\Operation`.
Operation can not be used to manipulate seeding data. See :ref:`Events <_developers_events>` for data manipulation.

.. code-block:: php
  :caption: Operation is executed before data seeding.

  <?php

  namespace MyVendor\MyExtension\Operation;

  use KM2\DataSeeder\Attribute\Operation;
  use KM2\DataSeeder\Operation\OperationInterface;

  #[Operation(identifier: 'my-custom-operation', beforeDataSeeding: true)]
  class MyCustomOperation implements OperationInterface
  {
      public function run(Configuration $configuration, VariableCollection $variables, ?RootNode $rootNode): void
      {
          // $rootNode is null for operations executed before data seeding.
          echo "This operation is executed before data seeding.";
      }
  }

.. code-block:: php
  :caption: Operation is executed after data seeding.

  <?php

  namespace MyVendor\MyExtension\Operation;

  use KM2\DataSeeder\Attribute\Operation;
  use KM2\DataSeeder\Operation\OperationInterface;

  #[Operation(identifier: 'my-custom-operation')]
  class MyCustomOperation implements OperationInterface
  {
      public function run(Configuration $configuration, VariableCollection $variables, ?RootNode $rootNode): void
      {
          echo "This operation is executed after data seeding.";
      }
  }
