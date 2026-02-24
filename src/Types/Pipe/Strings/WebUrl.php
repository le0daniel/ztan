<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Strings;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Uri\WhatWg\Url;

/**
 * @implements Pipe<string>
 */
final readonly class WebUrl implements Pipe
{
    private const HOSTNAME_PATTERN = '/^([a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/';

    public function execute(mixed $value, Context $context): string|Value
    {
        $url = Url::parse($value);

        if ($url === null) {
            $context->addIssue(Issue::invalidValue('String is not a valid web URL.', $value));
            return Value::INVALID;
        }

        $scheme = rtrim($url->getScheme(), ':');
        if ($scheme !== 'http' && $scheme !== 'https') {
            $context->addIssue(Issue::invalidValue('URL must use http or https protocol.', $value, [
                'actual_protocol' => $scheme,
            ]));
            return Value::INVALID;
        }

        $host = $url->getAsciiHost();
        if ($host === null || preg_match(self::HOSTNAME_PATTERN, $host) !== 1) {
            $context->addIssue(Issue::invalidValue('URL must have a valid domain name.', $value, [
                'actual_hostname' => $host,
            ]));
            return Value::INVALID;
        }

        return $value;
    }
}
