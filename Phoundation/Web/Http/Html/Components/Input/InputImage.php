<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;

/**
 * Class InputImage
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputImage extends Input
{
    /**
     * InputImage class constructor
     */
    public function __construct()
    {
        $this->type = InputType::image;
        parent::__construct();
    }
}