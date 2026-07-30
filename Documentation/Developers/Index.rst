.. include:: /Includes.rst.txt

..  _developers:

==========
Developers
==========

This extension can be extended in multiple places.

.. card-grid::
  :columns: 1
  :columns-md: 2
  :gap: 4
  :class: pb-4
  :card-height: 100

    .. card:: :ref:`Events <_developers_events>`

      List of events provided by the extension.

    .. card:: :ref:`Operations <_developers_operations>`

      Build new operations that can be run before or after data seeding.

    .. card:: :ref:`Loaders <_developers_loaders>`

      Register new loaders for loading seeding data.

    .. card:: :ref:`Processors <_developers_processors>`

      Register new processors for processing seeding data.

    .. card:: :ref:`Property Converters <_developers_property_converters>`

      Register new property converters.

..  toctree::
    :hidden:
    :maxdepth: 2
    :titlesonly:

    Events/Index
    Operations/Index
    Loaders/Index
    Processors/Index
    PropertyConverters/Index
