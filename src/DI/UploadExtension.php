<?php

/**
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Arachne\Upload\DI;

use Arachne\Forms\DI\FormsExtension;
use Kdyby\Validator\DI\ValidatorExtension;
use Nette\DI\CompilerExtension;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class UploadExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        if ($this->compiler->getExtensions('Arachne\Forms\DI\FormsExtension')) {
            $sf28 = method_exists('Symfony\Component\Form\AbstractType', 'getName');

            $names = ['Arachne\Upload\Type\FileType'];
            if ($sf28) {
                $names[] = 'file';
            }

            $builder->addDefinition($this->prefix('forms.file'))
                ->setClass('Arachne\Upload\Type\FileType')
                ->addTag(FormsExtension::TAG_TYPE, $names);
        }

        if ($this->compiler->getExtensions('Kdyby\Validator\DI\ValidatorExtension')) {
            $builder->addDefinition($this->prefix('validator.file'))
                ->setClass('Arachne\Upload\Constraint\FileValidator')
                ->addTag(ValidatorExtension::TAG_CONSTRAINT_VALIDATOR, 'Arachne\Upload\Constraint\FileValidator');
        }
    }
}
