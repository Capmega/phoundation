<?php

/**
 * SpanCore class
 *
 * This class contains the implementation of the Span class
 *
 * @see \Phoundation\Web\Html\Components\Span
 * @see \Phoundation\Web\Html\Components\Div
 * @see \Phoundation\Web\Html\Components\ElementCore
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Components\Interfaces\SpanInterface;
use Phoundation\Web\Html\Traits\TraitChildElement;


class SpanCore extends ElementCore implements SpanInterface
{
    use TraitChildElement;
}