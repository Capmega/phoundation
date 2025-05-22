<?php

/**
 * Button class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\DropdownButtonInterface;
use Phoundation\Web\Html\Traits\TraitInputElement;


class DropdownButton extends Buttons implements DropdownButtonInterface
{
    use TraitInputElement;
}
