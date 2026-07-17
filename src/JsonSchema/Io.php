<?php declare(strict_types=1);

namespace Le0daniel\Ztan\JsonSchema;

/**
 * Which side of validation the printed schema describes: the JSON-ish input a
 * type accepts, or the value parse() returns. Types whose given side is not
 * representable in JSON schema throw an UnsupportedTypeException.
 */
enum Io
{
    case Input;
    case Output;
}
