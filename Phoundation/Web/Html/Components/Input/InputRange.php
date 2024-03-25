<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\EnumInputType;


/**
 * Class InputRange
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputRange extends InputText
{
    /**
     * InputRange class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumInputType::range;
        parent::__construct($content);
    }
}