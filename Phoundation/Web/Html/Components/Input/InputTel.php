<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Traits\UsesAttributeMultiple;


/**
 * Class InputTel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputTel extends InputText
{
    use UsesAttributeMultiple;

    /**
     * InputTel class constructor
     */
    public function __construct()
    {
        $this->type = InputType::tel;
        parent::__construct();
    }
}