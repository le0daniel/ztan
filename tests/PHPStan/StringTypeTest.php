<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class StringTypeTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/cases/string-type.php');
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../extension.neon'];
    }
}
