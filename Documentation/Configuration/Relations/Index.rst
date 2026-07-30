.. include:: /Includes.rst.txt

..  _configuration_relations:

=========
Relations
=========

Relations can be handled by defining each record with properties referencing the parent record.
This is even possible for MM database relations.
But there is an easier and shorter way doing it.
Relations can be defined as array values for a property. Below are examples for each relation type

* :ref:`File references <_configuration_relations_files>`
* :ref:`Relations to existing records <_configuration_relations_existing_records>`
* :ref:`Relations to new records <_configuration_relations_new_records>`

.. _configuration_relations_files:

File references
---------------

File references require the property :bash:`file` to contain a sys_file reference.
Additional properties for `sys_file_reference` fields can be added.

.. code-block:: yaml

  sys_file:
    - identifier: example_jpg
      source: 'data/files/example.jpg'
      storage: default
      folder: /

  tt_content:
    - identifier: example
      pid: "{pages:home}"
      CType: image
      image:
        - file: "{sys_file:example_jpg}"
          alternative: "Lorem ipsum"
          title: "My fantastic title"

.. _configuration_relations_existing_records:

Relations to existing records
-----------------------------

Relations to existing records, no matter if its one-to-many, or many-to-many can be defined as an array of combined identifiers.

.. code-block:: yaml

  sys_category:
    - identifier: main-category-1
      pid: "{pages:home}"
      title: Main category 1
    - identifier: main-category-2
      pid: "{pages:home}"
      title: Main category 2

  pages:
    - identifier: home
      pid: "{pages:root}"
      title: Homepage
      doktype: 1
      is_siteroot: 1
      categories:
        - "{sys_category:main-category-1}"
        - "{sys_category:main-category-2}"

.. _configuration_relations_new_records:

Relations to new records
------------------------

Relations to records that do not exist and should created by the relations can also be defined a a simplified version.
The definition is done like every other records but as a property of the parent record.

.. note::

  If no pid is given, that pid from the parent record is used (except is parent record is a page).

.. code-block:: yaml

  pages:
    - identifier: home
      pid: "{pages:root}"
      title: Homepage
      doktype: 1
      is_siteroot: 1
      categories:
        sys_category:
          - identifier: main-category-1
            title: Main category 1
          - identifier: main-category-2
            title: Main category 2
