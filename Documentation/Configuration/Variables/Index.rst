.. include:: /Includes.rst.txt

..  _configuration_variables:

=========
Variables
=========

Variables can be defined for usage in records.
They are stored below the property :yaml:`_variables`.

Variables can be accessed by the keyword :yaml:`{variable:<variable-name>}`.

.. code-block:: yaml

  _variables:
    paragraph: "<p>Lorem ipsum dolor sit amet, <a href=\"t3://page?uid={pages:home}\">consetetur</a> sadipscing elitr</p>"

  tt_content:
    - identifier: example
      pid: "{pages:home}"
      CType: text
      bodytext: "{variable:paragraph}"
