<?php declare(strict_types=1);

namespace Le0daniel\Ztan\JsonSchema\Exceptions;

use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\JsonSchema\Io;
use RuntimeException;

final class UnsupportedTypeException extends RuntimeException
{
    /**
     * @param Type<mixed> $type
     */
    public static function forType(Type $type, Io $io): self
    {
        return new self(sprintf('Cannot print type %s to JSON schema (%s mode).', $type::class, $io->name));
    }
}
