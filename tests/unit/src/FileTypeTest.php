<?php

namespace Tests\Unit;

use Arachne\Upload\Type\FileType;
use Codeception\Test\Unit;
use Eloquent\Phony\Phpunit\Phony;
use Nette\Http\FileUpload;
use Symfony\Component\Form\Forms;

class FileTypeTest extends Unit
{
    protected function _before()
    {
        $this->factory = Forms::createFormFactoryBuilder()->getFormFactory();
    }

    public function testSetData()
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $data = $this->createUploadedFileMock('abcdef', 'original.jpg', true);
        $form->setData($data);
        $this->assertSame($data, $form->getData());
    }

    public function testSubmit()
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $data = $this->createUploadedFileMock('abcdef', 'original.jpg', true);
        $form->submit($data);
        $this->assertSame($data, $form->getData());
    }

    public function testSubmitEmpty()
    {
        $form = $this->factory->createBuilder(FileType::class)->getForm();
        $form->submit(null);
        $this->assertNull($form->getData());
    }

    public function testSubmitEmptyMultiple()
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

        $this->assertSame([], $form->getData());
    }

    public function testSetDataMultiple()
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
        $this->assertSame($data, $form->getData());
    }

    public function testSubmitMultiple()
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
        $this->assertSame($data, $form->getData());
        $view = $form->createView();
        $this->assertSame('arachne_file[]', $view->vars['full_name']);
        $this->assertArrayHasKey('multiple', $view->vars['attr']);
    }

    public function testDontPassValueToView()
    {
        $form = $this->factory->create(FileType::class);
        $form->submit([
            FileType::class => $this->createUploadedFileMock('abcdef', 'original.jpg', true),
        ]);
        $view = $form->createView();
        $this->assertEquals('', $view->vars['value']);
    }

    private function createUploadedFileMock($name, $originalName, $valid)
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
