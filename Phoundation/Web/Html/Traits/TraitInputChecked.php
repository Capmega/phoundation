<?php

/**
 * Trait TraitInputChecked
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;


trait TraitInputChecked
{
    /**
     * Returns if the checkbox is checked or not
     *
     * @return bool
     */
    public function getChecked(): bool
    {
        return (bool) $this->o_attributes->get('checked', false);
    }


    /**
     * Sets if the checkbox is checked or not
     *
     * @param bool $checked
     *
     * @return static
     */
    public function setChecked(bool $checked): static
    {
        if ($checked) {
            return $this->setAttribute('true', 'checked', false);
        }
        $this->getAttributesObject()
             ->removeKeys('checked');

        return $this;
    }
}
