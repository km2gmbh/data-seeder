.. include:: /Includes.rst.txt

..  _developers_loaders:

=======
Loaders
=======

This extension provides a loader to handle YAML files containing the seeding data.
See :ref:`Configuration file <_configuration_file>` for an example.

This section describes how custom loaders can be implemented.
A custom loader needs to implements the interface :php:`\KM2\DataSeeder\DataHandling\Loader\DataLoaderInterface`
and requires the PHP attribute :php:`\KM2\DataSeeder\Attribute\DataLoader` to be set.

.. code-block:: php
  :caption: Custom loader class example

  <?php

  namespace MyVendor\MyExtension\Loaders;

  use KM2\DataSeeder\Attribute\DataLoader;
  use KM2\DataSeeder\DataHandling\Loader\DataLoaderInterface;

  #[DataLoader(identifier: 'myLoader')]
  class MyCustomLoader implements DataLoaderInterface
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
