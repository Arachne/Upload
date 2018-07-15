<?php

declare(strict_types=1);

namespace Tests\Unit;

use Arachne\Forms\Extension\Application\Type\FormTypeApplicationExtension;
use Arachne\Upload\Type\FileType;
use Codeception\Test\Unit;
use Eloquent\Phony\Phpunit\Phony;
use Nette\Http\FileUpload;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class FileTypeTest extends Unit
{
    /**
     * @var FormFactoryInterface
     */
    private $factory;

    protected function _before(): void
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new FormTypeApplicationExtension())
            ->getFormFactory();
    }

    public function testSetData(): void
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $data = $this->createUploadedFileMock('abcdef', 'original.jpg', true);
        $form->setData($data);
        self::assertSame($data, $form->getData());
    }

    public function testSubmit(): void
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $data = $this->createUploadedFileMock('abcdef', 'original.jpg', true);
        $form->submit($data);
        self::assertSame($data, $form->getData());
    }

    public function testSubmitEmpty(): void
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $form->submit(null);
        self::assertNull($form->getData());
    }

    public function testSubmitEmptyMultiple(): void
    {
        $form = $this->factory
            ->createBuilder(
                FileType::class,
                null,
                [
                    'multiple' => true,
                ]
            )
            ->getForm();

        // submitted data when an input file is uploaded without choosing any file
        $form->submit([null]);

        self::assertSame([], $form->getData());
    }

    public function testSetDataMultiple(): void
    {
        $form = $this->factory
            ->createBuilder(
                FileType::class,
                null,
                [
                    'multiple' => true,
                ]
            )
            ->getForm();

        $data = [
            $this->createUploadedFileMock('abcdef', 'first.jpg', true),
            $this->createUploadedFileMock('zyxwvu', 'second.jpg', true),
        ];

        $form->setData($data);
        self::assertSame($data, $form->getData());
    }

    public function testSubmitMultiple(): void
    {
        $form = $this->factory
            ->createBuilder(
                FileType::class,
                null,
                [
                    'multiple' => true,
                ]
            )
            ->getForm();

        $data = [
            $this->createUploadedFileMock('abcdef', 'first.jpg', true),
            $this->createUploadedFileMock('zyxwvu', 'second.jpg', true),
        ];

        $form->submit($data);
        self::assertSame($data, $form->getData());
        $view = $form->createView();
        self::assertSame('arachne_file[]', $view->vars['full_name']);
        self::assertArrayHasKey('multiple', $view->vars['attr']);
    }

    public function testDontPassValueToView(): void
    {
        $form = $this->factory->create(FileType::class);
        $form->submit([
            FileType::class => $this->createUploadedFileMock('abcdef', 'original.jpg', true),
        ]);
        $view = $form->createView();
        self::assertEquals('', $view->vars['value']);
    }

    private function createUploadedFileMock(string $name, string $originalName, bool $valid): FileUpload
    {
        $fileHandle = Phony::mock(FileUpload::class);

        $fileHandle
            ->getTemporaryFile
            ->returns($name);

        $fileHandle
            ->getName
            ->returns($originalName);

        $fileHandle
            ->isOk
            ->returns($valid);

        return $fileHandle->get();
    }
}
