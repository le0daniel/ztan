<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

final readonly class Issue
{
    public function __construct(
        public string $expectedValueType,
    )
    {
    }
}