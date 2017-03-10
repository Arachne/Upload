<?php

namespace Arachne\Upload\Type;

use Nette\Http\FileUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as BaseFileType;
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
        $resolver->setDefaults(
            [
                'data_class' => FileUpload::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseFileType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'arachne_file';
    }
}
