<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Contracts;

use Le0daniel\Ztan\Data\Meta;

trait CarriesMetadata
{
    /**
     * Metadata is bound to this exact instance (Zod 4 semantics): any rebuild via
     * `new self(...)` — adding pipe(), extend(), omit() — intentionally drops it, so
     * call meta() last in a chain. Initialized lazily via clone() in meta(); the
     * uninitialized case is absorbed by ?? in getMeta() (see phpstan.neon suppression).
     * The explicit readonly keyword is required: readonly classes cannot use traits
     * with non-readonly properties.
     */
    protected readonly ?Meta $meta;

    /**
     * @param list<mixed>|null $examples
     */
    public function meta(
        ?string $description = null,
        ?string $title = null,
        ?array $examples = null,
        ?bool $deprecated = null,
    ): static
    {
        return clone($this, ['meta' => new Meta($description, $title, $examples, $deprecated)]);
    }

    public function getMeta(): ?Meta
    {
        return $this->meta ?? null;
    }
}
