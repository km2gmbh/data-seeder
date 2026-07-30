.. include:: /Includes.rst.txt

..  _seed_command:

============
Seed Command
============

The command :bash:`database:seed` will process the static data configured in the configuration file.

Options
=======

..  confval-menu::
    :name: confval-seed-options
    :display: table
    :type:
    :default:
    :required:

.. confval:: config
   :name: seed-config
   :required: false
   :default: seeder.yaml
   :type: string

   Path to the configuration file relative to project root.

.. confval:: reset
   :name: seed-reset
   :required: false
   :default: false
   :type: boolean

   When given, the database is flushed before seeding data onto it.

.. confval:: no-interaction
   :name: seed-no-interaction
   :required: false
   :default: false
   :type: boolean

   When set together with :bash:`reset` flag, no user confirmation is needed.
