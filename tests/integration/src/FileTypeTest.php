<?php

namespace Tests\Integration;

use Arachne\Bootstrap\Configurator;
use Arachne\Upload\Type\FileType;
use Codeception\MockeryModule\Test;
use Mockery;
use Nette\Http\FileUpload;
use Symfony\Component\Form\FormFactoryInterface;

class FileTypeTest extends Test
{
    protected function _before()
    {
        $config = new Configurator();
        $config->setTempDirectory(TEMP_DIR);
        $config->addConfig(__DIR__.'/../config/config.neon');
        $container = $config->createContainer();

        $this->factory = $container->getByType(FormFactoryInterface::class);
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

    public function testSubmitMultiple()
    {
        $form = $this->factory->createBuilder(FileType::class, null, [
            'multiple' => true,
        ])->getForm();
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
        $file = Mockery::mock(FileUpload::class);

        $file
            ->shouldReceive('getTemporaryFile')
            ->andReturn($name);

        $file
            ->shouldReceive('getName')
            ->andReturn($originalName);

        $file
            ->shouldReceive('isOk')
            ->andReturn($valid);

        return $file;
    }
}
