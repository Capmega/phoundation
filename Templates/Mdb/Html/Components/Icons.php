<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Components;

use Phoundation\Web\Http\Html\Renderer;


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
class Icons extends Renderer
{
    /**
     * Icons class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Icons $element)
    {
        parent::__construct($element);
    }


    /**
     * Render the icon HTML
     *
     * @note This render skips the parent Element class rendering for speed and simplicity
     * @return string|null
     */
    public function render(): ?string
    {
        $content = $this->element->getContent();

        if (preg_match('/[a-z0-9-_]*]/i', $content)) {
            // icon names should only have letters, numbers and dashes and underscores
            return $content;
        }

        return '<i class="fas fa-' . $content . ($this->element->getTier()->value ? ' fa-' . $this->element->getTier()->value : '') .'"></i>';
    }
}