.. include:: /Includes.rst.txt

..  _installation:

============
Installation
============

..  _installation-composer:

Install km2/data-seeder with Composer
=======================================

Install the extension via Composer:

..  code-block:: bash
    composer req --dev km2/data-seeder

See also `Installing extensions, TYPO3 Getting started <https://docs.typo3.org/permalink/t3start:installing-extensions>`_.

.. note::
   It is recommended to install this extension using the `--dev` flag.

..  _installation-classic:

Install km2/data-seeder in Classic Mode
=========================================

Or download the extension from
`https://extensions.typo3.org/extension/data_seeder <https://extensions.typo3.org/extension/data_seeder>`_
and install it in the Extension Manager.

.. warning::
   Do not install this extension on production systems.
   It shouldn't brake anything, but also shouldn't be needed.

Quick start
===========

This extension provides example data for creating a root page with a content element of type text.
To trigger the seed,
run the following command.

..  code-block:: bash
    vendor/bin/typo3 database:seed --config "EXT:data_seeder/Resources/Private/Example/seeder.yaml"
