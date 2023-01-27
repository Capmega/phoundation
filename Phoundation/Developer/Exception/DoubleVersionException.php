<?php

namespace Phoundation\Developer\Exception;



/**
 * Class DoubleVersionException
 *
 * This exception is thrown when an init version is specified twice in the Init.php file for each library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class DoubleVersionException extends DeveloperException
{
}
