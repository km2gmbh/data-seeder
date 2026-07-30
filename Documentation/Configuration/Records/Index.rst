.. include:: /Includes.rst.txt

..  _configuration_records:

=================
Records and files
=================

Creating new records follows a default scheme.
A table is defined as a property type on the root level and contains an array of records.
Properties for each record can be added using the database column names.

.. note::

  It is recommended to use a unique identifier for each record.
  Allowed characters in identifiers are :bash:`a-z`, :bash:`A-Z`, :bash:`0-9`, :bash:`-` and :bash:`_`.

.. code-block:: yaml

  pages:
    - identifier: home
      pid: "{pages:root}"
      sorting: 100
      doktype: 1
      title: Home
      # Slugs are generated automatically for pages
      is_siteroot: 1

  tt_content:
    - identifier: example
      pid: "{pages:home}"
      CType: heaader
      header: My example content
      header_layout: 1


Files
=====

Defining files is a special case.
All files are added to the defined storage using FAL method including meta data extractors.

.. code-block:: yaml
  :caption: Assuming the source file is located at <project-root>/data/files/example.jpg

  sys_file:
    - identifier: example_jpg
      source: 'data/files/example.jpg'
      storage: default
      folder: placeholder/

.. confval-menu::
  :name: confval-records-files
  :display: table
  :type:
  :required:

.. confval:: identifier
  :name: config-records-files-identifier
  :required: true
  :type: string

  A unique identifier for this file.

.. confval:: source
  :name: config-records-files-source
  :required: true
  :type: string

  Path to the file relative to project root or with :bash:`EXT:` prefix.

.. confval:: storage
  :name: config-records-files-storage
  :required: true
  :type: string

  The identifier for the storage that should be used.
  See :ref:`FAL storage options <_configuration_fal_storage>`.

.. confval:: folder
  :name: config-records-files-folder
  :required: true
  :type: string

  The target directory where the file is copied.
  Relative to storage base path.
  Directory is created if is does not exist.

