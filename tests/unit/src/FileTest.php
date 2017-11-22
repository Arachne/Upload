<?php

declare(strict_types=1);

namespace Tests\Unit;

use Arachne\Upload\Constraint\File;
use Codeception\Test\Unit;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class FileTest extends Unit
{
    /**
     * @dataProvider provideValidSizes
     *
     * @param int|string $maxSize
     */
    public function testMaxSize($maxSize, int $bytes, bool $binaryFormat): void
    {
        $file = new File(['maxSize' => $maxSize]);

        $this->assertSame($bytes, $file->maxSize);
        $this->assertSame($binaryFormat, $file->binaryFormat);
    }

    /**
     * @dataProvider provideValidSizes
     *
     * @param int|string $maxSize
     */
    public function testMaxSizeCanBeSetAfterInitialization($maxSize, int $bytes, bool $binaryFormat): void
    {
        $file = new File();
        $file->maxSize = $maxSize;

        $this->assertSame($bytes, $file->maxSize);
        $this->assertSame($binaryFormat, $file->binaryFormat);
    }

    /**
     * @dataProvider provideInvalidSizes
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     *
     * @param int|string $maxSize
     */
    public function testInvalidValueForMaxSizeThrowsExceptionAfterInitialization($maxSize): void
    {
        $file = new File(['maxSize' => 1000]);
        $file->maxSize = $maxSize;
    }

    /**
     * @dataProvider provideInvalidSizes
     *
     * @param int|string $maxSize
     */
    public function testMaxSizeCannotBeSetToInvalidValueAfterInitialization($maxSize): void
    {
        $file = new File(['maxSize' => 1000]);

        try {
            $file->maxSize = $maxSize;
        } catch (ConstraintDefinitionException $e) {
        }

        $this->assertSame(1000, $file->maxSize);
    }

    /**
     * @dataProvider provideInValidSizes
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testInvalidMaxSize(string $maxSize): void
    {
        new File(['maxSize' => $maxSize]);
    }

    public function provideValidSizes(): array
    {
        return [
            ['500', 500, false],
            [12300, 12300, false],
            ['1ki', 1024, true],
            ['1KI', 1024, true],
            ['2k', 2000, false],
            ['2K', 2000, false],
            ['1mi', 1048576, true],
            ['1MI', 1048576, true],
            ['3m', 3000000, false],
            ['3M', 3000000, false],
        ];
    }

    public function provideInvalidSizes(): array
    {
        return [
            ['+100'],
            ['foo'],
            ['1Ko'],
            ['1kio'],
            ['1G'],
            ['1Gi'],
        ];
    }

    /**
     * @dataProvider provideFormats
     *
     * @param int|string $maxSize
     */
    public function testBinaryFormat($maxSize, ?bool $guessedFormat, bool $binaryFormat): void
    {
        $file = new File(['maxSize' => $maxSize, 'binaryFormat' => $guessedFormat]);

        $this->assertSame($binaryFormat, $file->binaryFormat);
    }

    public function provideFormats(): array
    {
        return [
            [100, null, false],
            [100, true, true],
            [100, false, false],
            ['100K', null, false],
            ['100K', true, true],
            ['100K', false, false],
            ['100Ki', null, true],
            ['100Ki', true, true],
            ['100Ki', false, false],
        ];
    }
}
