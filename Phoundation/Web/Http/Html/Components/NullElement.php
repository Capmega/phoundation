<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Interfaces\EntryInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\NullElementInterface;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * NullElement class
 *
 * This is an empty element that will not render anything but its contents
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class NullElement extends Element implements NullElementInterface
{
    /**
     * NullElement class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setElement(null);
    }
}