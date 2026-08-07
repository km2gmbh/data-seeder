<?php

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Data seeder',
    'description' => 'This extension provides a command, that allows seeding data from YAML files into a TYPO3 database.',
    'category' => 'misc',
    'version' => '0.4.3',
    'constraints' => [
        'depends' => [
            'php' => '8.4.0-',
            'typo3' => '13.4.0-14.3.99',
            'typo3_console' => '8.0.2-9.99.99',
        ],
        'conflicts' => [],
    ],
    'state' => 'stable',
    'author' => 'KM2 >> GmbH Team',
    'author_email' => 'hallo@km2.de',
    'author_company' => 'KM2 >> GmbH',
];
