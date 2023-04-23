<?php

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;

/**
 * Class InputFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputFile extends Input
{
    /**
     * Defines what this file upload control will accept
     *
     * @var string|null $accept
     */
    protected ?string $accept = null;


    /**
     * InputFile class constructor
     */
    public function __construct()
    {
        $this->type = InputType::file;
        parent::__construct();
    }
}