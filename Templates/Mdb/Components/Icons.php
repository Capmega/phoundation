<?php

namespace Templates\Mdb\Components;



/**
 * MDB Plugin Icons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Icons extends \Phoundation\Web\Http\Html\Components\Icons
{
    /**
     * Render the icon HTML
     *
     * @note This render skips the parent Element class rendering for speed and simplicity
     * @return string|null
     */
    public function render(): ?string
    {
        if (preg_match('/[a-z0-9-_]*]/i', $this->content)) {
            // icon names should only have letters, numbers and dashes and underscores
            return $this->content;
        }

        return '<i class="fas fa-' . $this->content . ($this->size ? ' fa-' . $this->size : '') .'"></i>';
    }
}