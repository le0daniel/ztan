<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Scalars;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Strings\Email;
use Le0daniel\Ztan\Types\Pipe\Strings\EndsWith;
use Le0daniel\Ztan\Types\Pipe\Strings\IsEmpty;
use Le0daniel\Ztan\Types\Pipe\Strings\Lowercase;
use Le0daniel\Ztan\Types\Pipe\Strings\MaxLength;
use Le0daniel\Ztan\Types\Pipe\Strings\MinLength;
use Le0daniel\Ztan\Types\Pipe\Strings\NotEmpty;
use Le0daniel\Ztan\Types\Pipe\Strings\Regex;
use Le0daniel\Ztan\Types\Pipe\Strings\StartsWith;
use Le0daniel\Ztan\Types\Pipe\Strings\Trim;
use Le0daniel\Ztan\Types\Pipe\Strings\Uppercase;
use Le0daniel\Ztan\Types\Pipe\Strings\Url;
use Le0daniel\Ztan\Types\Pipe\Strings\WebUrl;

/**
 * @extends BaseType<string>
 */
final readonly class StringType extends BaseType
{
    /**
     * @param list<Pipe<string>> $pipeline
     */
    public function __construct(
        private array $pipeline = [],
        private bool $coerce = false
    )
    {
    }

    /**
     * @param Pipe<string> $pipe
     */
    private function withPipe(Pipe $pipe): self
    {
        return new self([...$this->pipeline, $pipe], $this->coerce);
    }

    public function trim(): self
    {
        return $this->withPipe(new Trim());
    }

    public function lowercase(): self
    {
        return $this->withPipe(new Lowercase());
    }

    public function uppercase(): self
    {
        return $this->withPipe(new Uppercase());
    }

    public function minLength(int $length, bool $including = true): self
    {
        return $this->withPipe(new MinLength($length, $including));
    }

    public function maxLength(int $length, bool $including = true): self
    {
        return $this->withPipe(new MaxLength($length, $including));
    }

    public function startsWith(string $prefix): self
    {
        return $this->withPipe(new StartsWith($prefix));
    }

    public function endsWith(string $suffix): self
    {
        return $this->withPipe(new EndsWith($suffix));
    }

    public function regex(string $pattern): self
    {
        return $this->withPipe(new Regex($pattern));
    }

    public function isEmpty(): self
    {
        return $this->withPipe(new IsEmpty());
    }

    public function notEmpty(): self
    {
        return $this->withPipe(new NotEmpty());
    }

    public function email(): self
    {
        return $this->withPipe(new Email());
    }

    public function url(?string $protocol = null, ?string $hostname = null, bool $normalize = false): self
    {
        return $this->withPipe(new Url($protocol, $hostname, $normalize));
    }

    public function webUrl(): self
    {
        return $this->withPipe(new WebUrl());
    }

    public static function coerceValue(mixed $value): mixed
    {
        return match (gettype($value)) {
            'boolean' => $value ? 'true' : 'false',
            'integer', 'double', 'string' => (string) $value,
            default => $value,
        };
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        $value = $this->coerce ? self::coerceValue($value) : $value;

        if (!is_string($value)) {
            $context->addIssue(Issue::invalidType("Expected string.", $value));
            return Value::INVALID;
        }

        foreach ($this->pipeline as $pipe) {
            $value = $pipe->execute($value, $context);
            if (Value::isInvalid($value)) {
                return $value;
            }
        }

        return $value;
    }
}