.. include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

This extension provides the option to import static data sets into the TYPO3 database.
This can be used to provide data for development teams or frontend tests.

Motivation
==========

Working in teams provides some challenges.
One of them is the distribution of development data.
As there are many ways to tackle this challenge,
we decided to provide static data that can grow during project progression and can be handled by VCS.

Quick start
===========

This extension provides example data for creating a root page with a content element of type text.
To trigger the seed,
run the following command.

..  code-block:: bash

    vendor/bin/typo3 database:seed --config "EXT:data_seeder/Resources/Private/Example/seeder.yaml"
