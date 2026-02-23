<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

enum IssueType: string
{
    case InvalidType = 'invalid_type';
    case InvalidValue = 'invalid_value';
    case MissingValue = 'missing_value';
    case Custom = 'custom';
}
