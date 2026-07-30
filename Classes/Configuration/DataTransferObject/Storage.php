<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Configuration\DataTransferObject;

final readonly class Storage
{
    /**
     * @param non-empty-string $identifier
     * @param non-empty-string $path
     */
    public function __construct(
        private string $identifier,
        private string $path,
        private bool $absolute,
        private bool $default,
        private ?string $baseUri = null
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return non-empty-string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function isAbsolute(): bool
    {
        return $this->absolute;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }
}
