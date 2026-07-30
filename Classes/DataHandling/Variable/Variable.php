<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Variable;

final readonly class Variable
{
    public function __construct(private string $name, private int|string|bool|float $value)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int|string|bool|float
    {
        return $this->value;
    }
}
