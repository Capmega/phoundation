<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Interfaces\EntryInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * Entry class
 *
 * This class can display a user form containing all the elements for a single database entry
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Entry extends NullElement implements EntryInterface
{
}