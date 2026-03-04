<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Integration\FullSchema\Schemas;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Tests\Integration\FullSchema\SchemaTestCase;
use Le0daniel\Ztan\Ztan;

final class LiteralUnionSchema implements SchemaTestCase
{

    public function schema(): BaseType
    {
        return Ztan::union(
            Ztan::literal('png'),
            Ztan::literal('jpg'),
            Ztan::literal('jpeg'),
            Ztan::literal('tif'),
            Ztan::literal('tiff'),
            Ztan::literal('webp'),
        );
    }

    public function passingValues(): iterable
    {
        return [
            "png" => ["png", "png"],
            "jpg" => ["jpg", "jpg"],
            "jpeg" => ["jpeg", "jpeg"],
            "tif" => ["tif", "tif"],
            "tiff" => ["tiff", "tiff"],
            "webp" => ["webp", "webp"],
        ];
    }

    public function failingValues(): iterable
    {
        return [
            "gif" => ["gif"],
            "bmp" => ["bmp"],
            "svg" => ["svg"],
        ];
    }
}