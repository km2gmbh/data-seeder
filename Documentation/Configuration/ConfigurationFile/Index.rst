.. include:: /Includes.rst.txt

..  _configuration_file:

==================
Configuration file
==================

This extension requires a configuration file in YAML format.
By default the extension is looking for a `seeder.yaml` file in your projects root directory.
This path can be overwritten when running the CLI command.

.. code-block:: yaml
  :caption: <project-root>/seeder.yaml

  data:
    loader: yaml
    options:
      type: 'folder'
      path: 'data/database/'

  fal:
    storages:
      - identifier: default
        storagePath: fileadmin/

  ordering:
    fe_users:
      after: [ "fe_groups" ]
    tt_content:
      after: [ "sys_category" ]

  operations:
    envFileGenerator:
      envFiles:
        pids:
          path: pids.env
          values:
            PID_404: "{pages:404:uid}"

.. confval-menu::
  :name: confval-config-root-options
  :display: table
  :type:
  :required:
  :exclude: config-data-loader-loader, config-data-loader-options, config-data-loader-options-type, config-data-loader-options-path, config-fal-storage-identifier, config-fal-storage-storage-path, config-fal-storage-base-uri

.. confval:: data
  :name: config-root-data
  :required: true
  :type: DataLoader

   Configuration for how the static data should be loaded. See :ref:`DataLoader <_configuration_data_loader>` options

.. confval:: fal
  :name: config-root-fal
  :required: true
  :type: object

   FAL configuration. By now only the key :yaml:`storages` is supported.

.. confval:: fal.storages
  :name: config-root-fal-storages
  :required: true
  :type: array

   Configuration for FAL resource storages that should be created.
   See :ref:`FAL storage options <_configuration_fal_storage>` for more details.

   .. note::

     The first storage configured is marked as default storage.

.. confval:: ordering
  :name: config-root-ordering
  :required: true
  :type: array

  An array of database tables for setting the ordering of record processing.
  Any table can be used as a key and might have two properties: :yaml:`before` and :yaml:`after`.
  Both keys are arrays themself and contain database table names.

  .. code-block:: yaml

    fe_users:
      after: [ "fe_groups" ]
    tt_content:
      after: [ "sys_category" ]

  .. note::

    The tables :bash:`pages` and :bash:`sys_file` are by default configured to the processed before the :bash:`tt_content` table.

.. confval:: operations
  :name: config-root-operations
  :required: true
  :type: object

  There can be additional operations before and after the seeding. See :ref:`operations <_operations>` page.


.. _configuration_data_loader:

DataLoader options
------------------

.. confval-menu::
  :name: confval-config-data-loader-options
  :display: table
  :type:
  :required:
  :exclude: config-root-data, config-root-fal, config-root-fal-storages, config-root-ordering, config-root-operations, config-fal-storage-identifier, config-fal-storage-storage-path, config-fal-storage-base-uri

.. confval:: loader
  :name: config-data-loader-loader
  :required: true
  :type: string

   Name of the registered data loader. Available loaders provided by this extension: :yaml:`yaml`.
   Additional loaders can be registered using dependency injection.

.. confval:: options
  :name: config-data-loader-options
  :required: true
  :type: array

  An array of options given to the data loader.

  .. code-block:: yaml

    type: 'folder'
    path: 'data/database/'

.. confval:: options.type
  :name: config-data-loader-options-type
  :required: true
  :type: string

  Either :yaml:`folder` or :yaml:`file`.

  Using :yaml:`folder` value, the loader will iterate through all subdirectories and load :bash:`*.yaml` files.

  Using :yaml:`file` value, the riles needs to provide all data for seeding (imports can be used).

  .. note::

    Only for :yaml:`yaml` data loader.

.. confval:: options.path
   :name: config-data-loader-options-path
   :required: true
   :type: string

  Relative path to the yaml file or folder, based on the type configuration.

  .. note::

    Only for :yaml:`yaml` data loader.


.. _configuration_fal_storage:

FAL storage options
------------------

.. confval-menu::
  :name: confval-config-fal-storage-options
  :display: table
  :type:
  :required:
  :exclude: config-root-data, config-root-fal, config-root-fal-storages, config-root-ordering, config-root-operations, config-data-loader-loader, config-data-loader-options, config-data-loader-options-type, config-data-loader-options-path,

.. confval:: identifier
  :name: config-fal-storage-identifier
  :required: true
  :type: string

   Unique identifier for this storage. Is used to assign files to this storage.

.. confval:: storagePath
  :name: config-fal-storage-storage-path
  :required: true
  :type: string

  Relative or absolute path for the storage.
  Is used as :bash:`basePath` in storage configuration.
  When starting with a :bash:`/`, that path is considered to be absolute.

.. confval:: baseUri
  :name: config-fal-storage-base-uri
  :required: false
  :type: string

  Set the :bash:`bashUri` configuration for this storage.
