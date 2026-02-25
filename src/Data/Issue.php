<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Data;

use Le0daniel\Ztan\Utils\Debug;
use Le0daniel\Ztan\Utils\Lists;

final readonly class Issue
{
    /**
     * @param string $message User facing message should be short and concise, not expose validation details.
     * @param list<int|string> $path
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string    $message,
        public IssueType $type,
        public array     $path = [],
        public mixed     $received = null,
        public array     $metadata = [],
        public ?string   $debugMessage = null,
    )
    {
    }

    /**
     * @param list<int|string> $path
     */
    public function prependPath(array $path): self
    {
        return clone($this, [
            'path' => [
                ... $path,
                ... $this->path,
            ],
        ]);
    }

    public function getPathAsString(): string
    {
        return implode('.', $this->path);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function invalidType(string $message, mixed $received, array $metadata = []): self
    {
        return new self(
            $message,
            IssueType::InvalidType,
            received: $received,
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function invalidValue(string $message, mixed $received, array $metadata = []): self
    {
        return new self(
            $message,
            IssueType::InvalidValue,
            received: $received,
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function missingValue(string $message, array $metadata = []): self
    {
        return new self(
            $message,
            IssueType::MissingValue,
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function custom(string $message, mixed $received = null, array $metadata = []): self
    {
        return new self(
            $message,
            IssueType::Custom,
            received: $received,
            metadata: $metadata,
        );
    }

    public function getMessage(bool $withDebugInformation = false): string
    {
        if (!$withDebugInformation) {
            return $this->message;
        }

        $receivedValue = Debug::getType($this->received);
        return implode(' ', Lists::filterNullValues([
            "[{$this->type->name}]",
            $this->message,
            "Received: {$receivedValue}.",
            $this->debugMessage
        ]));
    }
}
