<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\InputType;


/**
 * Class InputDateTimeLocal
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateTimeLocal extends InputText
{
    /**
     * InputDateTimeLocal class constructor
     */
    public function __construct()
    {
        $this->type = InputType::datetime_local;
        parent::__construct();
    }
}