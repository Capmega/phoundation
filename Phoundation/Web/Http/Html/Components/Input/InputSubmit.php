<?php

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;


/**
 * Class InputSubmit
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputSubmit extends Input
{
    /**
     * InputSubmit class constructor
     */
    public function __construct()
    {
        $this->type = InputType::submit;
        parent::__construct();
    }
}