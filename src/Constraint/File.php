<?php

/*
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

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
