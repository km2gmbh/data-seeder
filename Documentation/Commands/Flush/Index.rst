.. include:: /Includes.rst.txt

..  _flush_command:

=============
Flush Command
=============

The command :bash:`database:flush` flush all database data.
Command needs to be confirmed by user interaction in order to finish.

Options
=======

.. confval-menu::
  :name: confval-flush-options
  :display: table
  :type:
  :default:
  :required:

.. confval:: delete-tables
  :name: flush-delete-tables
  :required: false
  :default: false
  :type: boolean

  When set, tables will not only be flush (truncated) but also deleted.

.. confval:: connection
  :name: flush-connection
  :required: false
  :default: Default
  :type: string

  What connection should be used for running this command.

.. confval:: no-interaction
  :name: flush-no-interaction
  :required: false
  :default: false
  :type: boolean

  When set, no user confirmation is needed.
