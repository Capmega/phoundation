<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Element;
use Phoundation\Web\Http\Html\Components\Input\Traits\InputElement;


/**
 * Class Input
 *
 * This class gives basic <input> functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Input extends Element implements Interfaces\InputInterface
{
    use InputElement;


    /**
     * Input class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->requires_closing_tag = false;
        $this->element              = 'input';
    }


    /**
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->attributes = array_merge($this->buildInputAttributes(), $this->attributes);
        return parent::render();
    }
}