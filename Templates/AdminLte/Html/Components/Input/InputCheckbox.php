<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components\Input;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Class InputCheckbox
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputCheckbox extends Input
{
    /**
     * InputCheckbox class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputCheckbox $element)
    {
        $element->addClasses('form-check-input');
        parent::__construct($element);
    }


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $object = $this->getRenderobject();

        return '<div class="custom-control custom-checkbox">
                    ' . parent::render() . '
                    ' . ($object->getLabel() ? '<label for="' . $object->getId() . '" class="custom-control-label">' . $object->getLabel() . '</label>' : '') . '
                </div>';
    }
}
