.. include:: /Includes.rst.txt

..  _developers_events:

======
Events
======

This extension provides multiple PSR-14 events for modifying runtime behavior.

* :ref:`Before building node <_developers_events_before_building_node>`
* :ref:`Initialize node <_developers_events_initialize_node>`
* :ref:`Before processing node <_developers_events_before_processing_node>`

.. _developers_events_before_building_node:

Before building node
--------------------

Event: :php:`\KM2\DataSeeder\Event\BeforeNodeBuildingEvent`

This event is fired before the system builds the node tree that us used for importing.
All records for a recordType are passed to this event and can be modified.

.. _developers_events_initialize_node:

Initialize node
---------------

Event: :php:`\KM2\DataSeeder\Event\InitializeRecordNodeEvent`

This event is fired after :ref:`Before building node event <_developers_events_before_building_node>` but before the node is actional build.
The raw data that is used for building the node is passend.
A node can be created by a listener and set in the event.
The default node building is skipped and the passend node is used.
Passed node needs to implement :php:`\KM2\DataSeeder\DataHandling\Node\NodeInterface` interface.


.. _developers_events_before_processing_node:

Before processing node
----------------------

Event: :php:`\KM2\DataSeeder\Event\BeforeProcessingNodeEvent`

This event is fired before a node is processed by a processor.
Each node is passed to the event and can be can be modified.
