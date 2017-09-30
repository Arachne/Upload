<?php

declare(strict_types=1);

namespace Arachne\Upload\Constraint;

use Symfony\Component\Validator\Constraints\File as BaseFileConstraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class File extends BaseFileConstraint
{
}
