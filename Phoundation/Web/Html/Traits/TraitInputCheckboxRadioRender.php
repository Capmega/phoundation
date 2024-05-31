<?php

/**
 * Trait TraitInputCheckRadioRender
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Label;

trait TraitInputCheckboxRadioRender
{
    use TraitInputLabel;

    /**
     * Render the HTML for this checkbox
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Automatically give the component ID the same value as the name so that label "for" will work correctly
        if(!$this->getId()) {
            $this->setId($this->getName());
        }

        $this->getAria()->add($this->label, 'label');

        if ($this->label_hidden) {
            // Hide the label, apply it to aria only
            $this->label = null;
        }

        return parent::render();
    }
}
