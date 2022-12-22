<?php

namespace Phoundation\Web\Http\Html\Elements\Input;



use Phoundation\Web\Http\Html\Elements\Element;

/**
 * Class Input
 *
 * This trait adds functionality for HTML input elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Input extends Element
{
    use InputElement;



    /**
     * Input class constructor
     */
    public function __construct()
    {
        $this->element = 'input';
        parent::__construct();
    }
}