<?php
/**
 * Class InputTime
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\EnumInputType;

class InputTime extends InputText
{
    /**
     * InputTime class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumInputType::time;
        parent::__construct($content);
    }
}