<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Integration\FullSchema;

use Le0daniel\Assertions\Contracts\BaseType;

interface SchemaTestCase
{
    public function schema(): BaseType;

    public function expectedPhpStanType(): string;

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public function passingValues(): iterable;

    /**
     * @return iterable<string, array{mixed}>
     */
    public function failingValues(): iterable;
}
