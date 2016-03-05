<?php

/**
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Arachne\Upload\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class FileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Nette\Http\FileUpload',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\FileType';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        // Cannot be just 'file' because of a bug.
        // @link https://github.com/symfony/symfony/pull/17874
        // @todo Remove this method when the bug gets fixed and increase version constraint in composer.json.
        return 'arachne_file';
    }
}
