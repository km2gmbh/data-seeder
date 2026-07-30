.. include:: /Includes.rst.txt

..   _configuration_operations:

==========
Operations
==========

Operations make it possible to execute before or after the data seeding happens.
The configuration is done inside the main configuration file and not along the seed data.
This extension provides the following operations by default:

Operations are configured below the :bash:`operations` property.
Each operation requires a unique identifier that contains the operation configuration.

For creating custom operations, see :ref:`Operations <_developers_operations>` page.

DotEnv Generator
================

This operation generates an env-file with configurable contents.
Mostly used to get UIDs mapped to an env variable that can be used in TYPO3 (e.g. for 404 page).

.. code-block:: yaml

  operations:
      envFileGenerator:
        envFiles:
          pids:
            path: pids.env
            values:
              PID_404: "{pages:404:uid}"

.. confval-menu::
  :name: confval-config-operations-env-generator
  :display: table
  :type:
  :required:

.. confval:: envFiles
  :name: confval-config-operations-env-generator-env-files
  :required: true
  :type: object

   Can contain multiple key/identifiers. Each key represents one env-file.

.. confval:: envFiles.<identifier>.path
  :name: confval-config-operations-env-generator-env-files-path
  :required: true
  :type: string

  Relative path and filename for the env-file that should be generated.

.. confval:: envFiles.<identifier>.values
  :name: confval-config-operations-env-generator-env-files-values
  :required: true
  :type: object

  An object containing key/value pairs that are put into the env-file.
  Values can use variables and combined identifiers as well as static values.

  .. code-block:: yaml

    values:
      IS_SEEDED_DATA: "1"
      PID_404: "{pages:404:uid}"
