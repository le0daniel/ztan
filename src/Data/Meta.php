<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Data;

final readonly class Meta
{
    /**
     * @param list<mixed>|null $examples
     */
    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
        public ?array $examples = null,
        public ?bool $deprecated = null,
    )
    {
    }
}
