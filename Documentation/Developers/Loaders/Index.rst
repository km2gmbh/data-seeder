.. include:: /Includes.rst.txt

..  _developers_loaders:

=======
Loaders
=======

Additional loaders can be registered for loading seeding data.
By default this extension ship a YAML loader.
See :ref:`Configuration file <_configuration_file>` for an example.

Additional loaders can be registered using the PHP attribute :php:`\KM2\DataSeeder\Attribute\DataLoader`.

.. code-block:: php
  :caption: Custom loader class example

  <?php

  namespace MyVendor\MyExtension\Loaders;

  use KM2\DataSeeder\Attribute\DataLoader;
  use KM2\DataSeeder\DataHandling\Loader\DataLoaderInterface;

  #[DataLoader(identifier: 'myLoader')]
  class MyCustomOperation implements OperationInterface
  {
      public function load(array $options = []): SeedingData
      {
          $variables = new VariableCollection($options['variables']);
          $staticData = [
              'pages' => [
                  [
                      'identifier' => 'home',
                      'pid' => '{pages:root}',
                      'title' => 'Home',
                      'doktype' => '{variable:defaultPageType}',
                  ],
              ],
          ];

          return new SeedingData($staticData, $variables);
      }
  }

.. code-block:: yaml
  :caption: Custom loader configuration

  data:
    loader: myLoader
    options:
      variables:
        defaultPageType: 1
