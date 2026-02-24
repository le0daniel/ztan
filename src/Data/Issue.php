<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

use Le0daniel\Assertions\Utils\Debug;

final readonly class Issue
{
    /**
     * @param string $message User facing message, should be short and concise, not expose validation details.
     * @param list<int|string> $path
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $message,
        public IssueType $type,
        public array $path = [],
        public mixed $received = null,
        public array $metadata = [],
        public string $debugMessage = '',
    ) {
    }

    /**
     * @param list<int|string> $path
     */
    public function withPath(array $path): self
    {
        return new self(
            $this->message,
            $this->type,
            $path,
            $this->received,
            $this->metadata,
            $this->debugMessage,
        );
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
            debugMessage: "{$message} Received: " . Debug::getType($received),
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
            debugMessage: "{$message} Received: " . Debug::getType($received),
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
            debugMessage: $message,
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
            debugMessage: "{$message} Received: " . Debug::getType($received),
        );
    }
}
