<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Data;

use Le0daniel\Ztan\Utils\Dicts;

final readonly class ParseError
{
    /**
     * @param list<Issue> $issues
     */
    public function __construct(
        public array $issues,
    ) {
    }

    public function toException(): ValidationException
    {
        return new ValidationException($this->issues);
    }

    public function errorMessage(bool $includeDebugInformation = false): string
    {
        /** @var array<string, list<string>> $groupedIssuesByPath */
        $groupedIssuesByPath = array_reduce($this->issues, function (array $carry, Issue $issue) use ($includeDebugInformation) {
            // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
            $carry[$issue->getPathAsString()][] = $issue->getMessage(withDebugInformation: $includeDebugInformation);
            return $carry;
        }, []);

        return Dicts::mapWithKeys(
            $groupedIssuesByPath,
            fn(string $path, array $messages): string => implode(PHP_EOL, [
                "At: " . ($path === '' ? 'root' : $path),
                ... array_map(fn(string $message) => "  - " . $message, $messages),
            ]),
        ) |> (fn(array $messages) => implode(PHP_EOL, $messages));
    }
}
