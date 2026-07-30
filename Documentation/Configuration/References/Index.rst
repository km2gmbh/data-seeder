.. include:: /Includes.rst.txt

..  _configuration_references:

==========
References
==========

Without providing fixed UIDs, records can be referenced by their identifiers.
They follow the scheme :yaml:`{<tablename>:<identifier>:<fieldname>}`.
The fieldname is optional. If not given, the UID field is ised for replacement.

There are some reserved / special identifiers:

- :yaml:`{variable:<variable-name>}`: See :ref:`variables <_configuration_variables>`.
- :yaml:`{pages:root}`: Will be replaced with :yaml:`0`.

.. code-block:: yaml

  pages:
    - identifier: home
      pid: "{pages:root}"
      sorting: 100
      doktype: 1
      title: Home
      is_siteroot: 1

  tt_content:
    - identifier: example
      pid: "{pages:home}"
      CType: heaader
      header: My example content
      header_layout: 1
