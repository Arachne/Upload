<?php

declare(strict_types=1);

namespace Tests\Unit;

use Arachne\Upload\Constraint\File;
use Arachne\Upload\Constraint\FileValidator;
use Nette\Http\FileUpload;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FileValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var resource
     */
    protected $file;

    protected function createValidator(): FileValidator
    {
        return new FileValidator();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = __DIR__.'/../../_temp/FileValidatorTest';

        $resource = fopen($this->path, 'w');

        if ($resource === false) {
            self::fail();

            return;
        }

        $this->file = $resource;
        fwrite($this->file, ' ', 1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        fclose($this->file);

        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new File());

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new File());

        self::assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleTypeOrFile(): void
    {
        $this->validator->validate(new \stdClass(), new File());
    }

    public function testValidFile(): void
    {
        $this->validator->validate($this->path, new File());

        self::assertNoViolation();
    }

    public function testValidUploadedfile(): void
    {
        $file = new FileUpload(
            [
                'name' => 'originalName',
                'type' => 'mime',
                'size' => 0,
                'tmp_name' => $this->path,
                'error' => 0,
            ]
        );

        $this->validator->validate($file, new File());

        self::assertNoViolation();
    }

    public function provideMaxSizeExceededTests(): array
    {
        // We have various interesting limit - size combinations to test.
        // Assume a limit of 1000 bytes (1 kB). Then the following table
        // lists the violation messages for different file sizes:
        // -----------+--------------------------------------------------------
        // Size       | Violation Message
        // -----------+--------------------------------------------------------
        // 1000 bytes | No violation
        // 1001 bytes | "Size of 1001 bytes exceeded limit of 1000 bytes"
        // 1004 bytes | "Size of 1004 bytes exceeded limit of 1000 bytes"
        //            | NOT: "Size of 1 kB exceeded limit of 1 kB"
        // 1005 bytes | "Size of 1.01 kB exceeded limit of 1 kB"
        // -----------+--------------------------------------------------------

        // As you see, we have two interesting borders:

        // 1000/1001 - The border as of which a violation occurs
        // 1004/1005 - The border as of which the message can be rounded to kB

        // Analogous for kB/MB.

        // Prior to Symfony 2.5, violation messages are always displayed in the
        // same unit used to specify the limit.

        // As of Symfony 2.5, the above logic is implemented.
        return [
            // limit in bytes
            [1001, 1000, '1001', '1000', 'bytes'],
            [1004, 1000, '1004', '1000', 'bytes'],
            [1005, 1000, '1.01', '1', 'kB'],

            [1000001, 1000000, '1000001', '1000000', 'bytes'],
            [1004999, 1000000, '1005', '1000', 'kB'],
            [1005000, 1000000, '1.01', '1', 'MB'],

            // limit in kB
            [1001, '1k', '1001', '1000', 'bytes'],
            [1004, '1k', '1004', '1000', 'bytes'],
            [1005, '1k', '1.01', '1', 'kB'],

            [1000001, '1000k', '1000001', '1000000', 'bytes'],
            [1004999, '1000k', '1005', '1000', 'kB'],
            [1005000, '1000k', '1.01', '1', 'MB'],

            // limit in MB
            [1000001, '1M', '1000001', '1000000', 'bytes'],
            [1004999, '1M', '1005', '1000', 'kB'],
            [1005000, '1M', '1.01', '1', 'MB'],

            // limit in KiB
            [1025, '1Ki', '1025', '1024', 'bytes'],
            [1029, '1Ki', '1029', '1024', 'bytes'],
            [1030, '1Ki', '1.01', '1', 'KiB'],

            [1048577, '1024Ki', '1048577', '1048576', 'bytes'],
            [1053818, '1024Ki', '1029.12', '1024', 'KiB'],
            [1053819, '1024Ki', '1.01', '1', 'MiB'],

            // limit in MiB
            [1048577, '1Mi', '1048577', '1048576', 'bytes'],
            [1053818, '1Mi', '1029.12', '1024', 'KiB'],
            [1053819, '1Mi', '1.01', '1', 'MiB'],
        ];
    }

    /**
     * @dataProvider provideMaxSizeExceededTests
     *
     * @param int|string $limit
     */
    public function testMaxSizeExceeded(int $bytesWritten, $limit, string $sizeAsString, string $limitAsString, string $suffix): void
    {
        fseek($this->file, $bytesWritten - 1, SEEK_SET);
        fwrite($this->file, '0');

        $constraint = new File(
            [
                'maxSize' => $limit,
                'maxSizeMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($this->getFile($this->path), $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ limit }}', $limitAsString)
            ->setParameter('{{ size }}', $sizeAsString)
            ->setParameter('{{ suffix }}', $suffix)
            ->setParameter('{{ file }}', '"'.$this->path.'"')
            ->setCode(File::TOO_LARGE_ERROR)
            ->assertRaised();
    }

    public function provideMaxSizeNotExceededTests(): array
    {
        return [
            // limit in bytes
            [1000, 1000],
            [1000000, 1000000],

            // limit in kB
            [1000, '1k'],
            [1000000, '1000k'],

            // limit in MB
            [1000000, '1M'],

            // limit in KiB
            [1024, '1Ki'],
            [1048576, '1024Ki'],

            // limit in MiB
            [1048576, '1Mi'],
        ];
    }

    /**
     * @dataProvider provideMaxSizeNotExceededTests
     *
     * @param int|string $limit
     */
    public function testMaxSizeNotExceeded(int $bytesWritten, $limit): void
    {
        fseek($this->file, $bytesWritten - 1, SEEK_SET);
        fwrite($this->file, '0');

        $constraint = new File(
            [
                'maxSize' => $limit,
                'maxSizeMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($this->getFile($this->path), $constraint);

        self::assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testInvalidMaxSize(): void
    {
        $constraint = new File(
            [
                'maxSize' => '1abc',
            ]
        );

        $this->validator->validate($this->path, $constraint);
    }

    public function provideBinaryFormatTests(): array
    {
        return [
            [11, 10, null, '11', '10', 'bytes'],
            [11, 10, true, '11', '10', 'bytes'],
            [11, 10, false, '11', '10', 'bytes'],

            // round(size) == 1.01kB, limit == 1kB
            [ceil(1000 * 1.01), 1000, null, '1.01', '1', 'kB'],
            [ceil(1000 * 1.01), '1k', null, '1.01', '1', 'kB'],
            [ceil(1024 * 1.01), '1Ki', null, '1.01', '1', 'KiB'],

            [ceil(1024 * 1.01), 1024, true, '1.01', '1', 'KiB'],
            [ceil(1024 * 1.01 * 1000), '1024k', true, '1010', '1000', 'KiB'],
            [ceil(1024 * 1.01), '1Ki', true, '1.01', '1', 'KiB'],

            [ceil(1000 * 1.01), 1000, false, '1.01', '1', 'kB'],
            [ceil(1000 * 1.01), '1k', false, '1.01', '1', 'kB'],
            [ceil(1024 * 1.01 * 10), '10Ki', false, '10.34', '10.24', 'kB'],
        ];
    }

    /**
     * @dataProvider provideBinaryFormatTests
     *
     * @param int|string $limit
     */
    public function testBinaryFormat(int $bytesWritten, $limit, ?bool $binaryFormat, string $sizeAsString, string $limitAsString, string $suffix): void
    {
        fseek($this->file, $bytesWritten - 1, SEEK_SET);
        fwrite($this->file, '0');

        $constraint = new File(
            [
                'maxSize' => $limit,
                'binaryFormat' => $binaryFormat,
                'maxSizeMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($this->getFile($this->path), $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ limit }}', $limitAsString)
            ->setParameter('{{ size }}', $sizeAsString)
            ->setParameter('{{ suffix }}', $suffix)
            ->setParameter('{{ file }}', '"'.$this->path.'"')
            ->setCode(File::TOO_LARGE_ERROR)
            ->assertRaised();
    }

    public function testValidMimeType(): void
    {
        $file = $this
            ->getMockBuilder(FileUpload::class)
            ->setConstructorArgs([__DIR__.'/../fixtures/foo'])
            ->getMock();
        $file
            ->expects(self::once())
            ->method('getTemporaryFile')
            ->will(self::returnValue($this->path));
        $file
            ->expects(self::once())
            ->method('getContentType')
            ->will(self::returnValue('image/jpg'));
        $file
            ->expects(self::once())
            ->method('isOk')
            ->will(self::returnValue(true));

        $constraint = new File(
            [
                'mimeTypes' => ['image/png', 'image/jpg'],
            ]
        );

        $this->validator->validate($file, $constraint);

        self::assertNoViolation();
    }

    public function testValidWildcardMimeType(): void
    {
        $file = $this
            ->getMockBuilder(FileUpload::class)
            ->setConstructorArgs([__DIR__.'/../fixtures/foo'])
            ->getMock();
        $file
            ->expects(self::once())
            ->method('getTemporaryFile')
            ->will(self::returnValue($this->path));
        $file
            ->expects(self::once())
            ->method('getContentType')
            ->will(self::returnValue('image/jpg'));
        $file
            ->expects(self::once())
            ->method('isOk')
            ->will(self::returnValue(true));

        $constraint = new File(
            [
                'mimeTypes' => ['image/*'],
            ]
        );

        $this->validator->validate($file, $constraint);

        self::assertNoViolation();
    }

    public function testInvalidMimeType(): void
    {
        $file = $this
            ->getMockBuilder(FileUpload::class)
            ->setConstructorArgs([__DIR__.'/../fixtures/foo'])
            ->getMock();
        $file
            ->expects(self::once())
            ->method('getTemporaryFile')
            ->will(self::returnValue($this->path));
        $file
            ->expects(self::once())
            ->method('getContentType')
            ->will(self::returnValue('application/pdf'));
        $file
            ->expects(self::once())
            ->method('isOk')
            ->will(self::returnValue(true));

        $constraint = new File(
            [
                'mimeTypes' => ['image/png', 'image/jpg'],
                'mimeTypesMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($file, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ type }}', '"application/pdf"')
            ->setParameter('{{ types }}', '"image/png", "image/jpg"')
            ->setParameter('{{ file }}', '"'.$this->path.'"')
            ->setCode(File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testInvalidWildcardMimeType(): void
    {
        $file = $this
            ->getMockBuilder(FileUpload::class)
            ->setConstructorArgs([__DIR__.'/../fixtures/foo'])
            ->getMock();
        $file
            ->expects(self::once())
            ->method('getTemporaryFile')
            ->will(self::returnValue($this->path));
        $file
            ->expects(self::once())
            ->method('getContentType')
            ->will(self::returnValue('application/pdf'));
        $file
            ->expects(self::once())
            ->method('isOk')
            ->will(self::returnValue(true));

        $constraint = new File(
            [
                'mimeTypes' => ['image/*', 'image/jpg'],
                'mimeTypesMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($file, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ type }}', '"application/pdf"')
            ->setParameter('{{ types }}', '"image/*", "image/jpg"')
            ->setParameter('{{ file }}', '"'.$this->path.'"')
            ->setCode(File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testDisallowEmpty(): void
    {
        ftruncate($this->file, 0);

        $constraint = new File(
            [
                'disallowEmptyMessage' => 'myMessage',
            ]
        );

        $this->validator->validate($this->getFile($this->path), $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ file }}', '"'.$this->path.'"')
            ->setCode(File::EMPTY_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider uploadedFileErrorProvider
     */
    public function testUploadedFileError(int $error, string $message, array $params = [], ?string $maxSize = null): void
    {
        $file = new FileUpload(
            [
                'name' => 'originalName',
                'type' => 'mime',
                'size' => 0,
                'tmp_name' => '/path/to/file',
                'error' => $error,
            ]
        );

        $constraint = new File(
            [
                $message => 'myMessage',
                'maxSize' => $maxSize,
            ]
        );

        $this->validator->validate($file, $constraint);

        $this->buildViolation('myMessage')
            ->setParameters($params)
            ->setCode($error)
            ->assertRaised();
    }

    public function uploadedFileErrorProvider(): array
    {
        $tests = [
            [UPLOAD_ERR_FORM_SIZE, 'uploadFormSizeErrorMessage'],
            [UPLOAD_ERR_PARTIAL, 'uploadPartialErrorMessage'],
            [UPLOAD_ERR_NO_FILE, 'uploadNoFileErrorMessage'],
            [UPLOAD_ERR_NO_TMP_DIR, 'uploadNoTmpDirErrorMessage'],
            [UPLOAD_ERR_CANT_WRITE, 'uploadCantWriteErrorMessage'],
            [UPLOAD_ERR_EXTENSION, 'uploadExtensionErrorMessage'],
        ];

        if (class_exists(FileUpload::class)) {
            // when no maxSize is specified on constraint, it should use the ini value
            $tests[] = [
                UPLOAD_ERR_INI_SIZE,
                'uploadIniSizeErrorMessage',
                [
                    '{{ limit }}' => FileValidator::getMaxFilesize() / 1048576,
                    '{{ suffix }}' => 'MiB',
                ],
            ];

            // it should use the smaller limitation (maxSize option in this case)
            $tests[] = [
                UPLOAD_ERR_INI_SIZE,
                'uploadIniSizeErrorMessage',
                [
                    '{{ limit }}' => 1,
                    '{{ suffix }}' => 'bytes',
                ],
                '1',
            ];

            // it correctly parses the maxSize option and not only uses simple string comparison
            // 1000M should be bigger than the ini value
            $tests[] = [
                UPLOAD_ERR_INI_SIZE,
                'uploadIniSizeErrorMessage',
                [
                    '{{ limit }}' => FileValidator::getMaxFilesize() / 1048576,
                    '{{ suffix }}' => 'MiB',
                ],
                '1000M',
            ];

            // it correctly parses the maxSize option and not only uses simple string comparison
            // 1000M should be bigger than the ini value
            $tests[] = [
                UPLOAD_ERR_INI_SIZE,
                'uploadIniSizeErrorMessage',
                [
                    '{{ limit }}' => '0.1',
                    '{{ suffix }}' => 'MB',
                ],
                '100K',
            ];
        }

        return $tests;
    }

    public function testFileNotFound(): void
    {
        $constraint = new File(
            [
                'notFoundMessage' => 'myMessage',
            ]
        );

        $this->validator->validate('foobar', $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ file }}', '"foobar"')
            ->setCode(File::NOT_FOUND_ERROR)
            ->assertRaised();
    }

    protected function getFile(?string $filename): ?string
    {
        return $filename;
    }
}
