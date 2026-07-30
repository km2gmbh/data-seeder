<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Configuration\DataTransferObject;

final readonly class DataLoader
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(private string $type, private array $options = [])
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
