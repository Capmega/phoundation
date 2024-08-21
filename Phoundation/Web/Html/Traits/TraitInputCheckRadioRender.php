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


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Web\Html\Components\Label;


trait TraitInputCheckRadioRender
{
    use TraitInputLabel;

    /**
     * Render the HTML for this checkbox
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->getAria()
             ->add($this->label, 'label');
        if ($this->label_hidden) {
            // Hide the label, put it in aria instead
            $this->label = null;

        } elseif ($this->label) {
            $element = Label::new()
                            ->setClass($this->label_class)
                            ->setContent($this->label);
            $element->getAttributes()
                    ->add($this->id, 'for');

            return parent::render() . $element->render();
        }

        return parent::render();
    }
}
