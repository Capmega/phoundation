<?php

/**
 * Class WidgetCore
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Web\Html\Components\ElementsBlockCore;
use Phoundation\Web\Html\Components\Widgets\Interfaces\WidgetInterface;
use Phoundation\Web\Html\Traits\TraitBackground;
use Phoundation\Web\Html\Traits\TraitGradient;
use Phoundation\Web\Html\Traits\TraitMode;


class WidgetCore extends ElementsBlockCore implements WidgetInterface
{
    use TraitMode;
    use TraitBackground;
    use TraitGradient;
}
