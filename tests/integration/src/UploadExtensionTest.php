<?php

namespace Tests\Integration;

use Arachne\Bootstrap\Configurator;
use Codeception\Test\Unit;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class UploadExtensionTest extends Unit
{
    public function testConfiguration()
    {
        $container = $this->createContainer('config.neon');


    }

    private function createContainer($file)
    {
        $config = new Configurator();
        $config->setTempDirectory(TEMP_DIR);
        $config->addConfig(__DIR__ . '/../config/' . $file);
        return $config->createContainer();
    }
}
