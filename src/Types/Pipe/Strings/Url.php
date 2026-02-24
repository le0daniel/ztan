<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Uri\WhatWg\Url as WhatWgUrl;

/**
 * @implements Pipe<string>
 */
final readonly class Url implements Pipe
{
    public function __construct(
        private ?string $protocol = null,
        private ?string $hostname = null,
        private bool $normalize = false,
    ) {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        $url = WhatWgUrl::parse($value);

        if ($url === null) {
            $context->addIssue(Issue::invalidValue('String is not a valid URL.', $value));
            return Value::INVALID;
        }

        if ($this->protocol !== null) {
            $scheme = rtrim($url->getScheme(), ':');
            if ($scheme !== $this->protocol) {
                $context->addIssue(Issue::invalidValue('URL protocol does not match.', $value, [
                    'expected_protocol' => $this->protocol,
                    'actual_protocol' => $scheme,
                ]));
                return Value::INVALID;
            }
        }

        if ($this->hostname !== null) {
            $host = $url->getAsciiHost();
            if ($host !== $this->hostname) {
                $context->addIssue(Issue::invalidValue('URL hostname does not match.', $value, [
                    'expected_hostname' => $this->hostname,
                    'actual_hostname' => $host,
                ]));
                return Value::INVALID;
            }
        }

        if ($this->normalize) {
            return $url->toAsciiString();
        }

        return $value;
    }
}
