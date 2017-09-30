<?php

namespace Arachne\Upload\Type;

use Nette\Http\FileUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as BaseFileType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class FileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => function (Options $options) {
                    return $options['multiple'] ? null : FileUpload::class;
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return BaseFileType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'arachne_file';
    }
}
