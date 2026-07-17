<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Contracts;

use Le0daniel\Ztan\Data\Meta;

interface HasMetadata
{
    /**
     * @param list<mixed>|null $examples
     */
    public function meta(
        ?string $description = null,
        ?string $title = null,
        ?array $examples = null,
        ?bool $deprecated = null,
    ): static;

    public function getMeta(): ?Meta;
}
