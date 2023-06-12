<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinition;
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
abstract class Input extends Element implements Interfaces\Input
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
     * Returns a new input element from
     *
     * @param DataEntryFieldDefinition $field
     * @return static
     */
    public static function newFromDAtaEntryField(DataEntryFieldDefinition $field): static
    {
        $element    = new static();
        $attributes = $field->getDefinitions();

        // Set all attributes from the definitions file
        foreach($attributes as $key => $value) {
            $method = 'set' . Strings::capitalize($key);

            if (method_exists($element, $method)) {
                $element->$method($value);
            }
        }

        return $element;
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