<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Integration\FullSchema;

use Le0daniel\Ztan\Contracts\BaseType;

interface SchemaTestCase
{
    public function schema(): BaseType;

    /**
     * The expected JSON schema for schema(), printed with the default printer
     * (Io::Input, additionalProperties: false). Return null to skip the assertion.
     *
     * @return array<string, mixed>|null
     */
    public function jsonSchema(): ?array;

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public function passingValues(): iterable;

    /**
     * @return iterable<string, array{mixed}>
     */
    public function failingValues(): iterable;
}
