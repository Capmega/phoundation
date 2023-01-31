<?php

namespace Templates\Mdb\Html\Components;

use Phoundation\Web\Http\Html\Components\Section;
use Phoundation\Web\Http\Html\Renderer;



/**
 * MDB Plugin Table class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Table extends Renderer
{
    /**
     * Table class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Table $element)
    {
        $element->addClass('table');
        parent::__construct($element);
    }



    /**
     * Render the MDB table
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Render the table
        $table = parent::render();

        // Render the section around it
        $return = Section::new()
            ->addClass($this->element->getFullWidth() ? 'w-100' : null)
            ->addClass($this->element->getResponsive() ? 'table-responsive' : null)
            ->setContent($table)
            ->render();

        // Render the section around it
        $return = Section::new()
            ->addClass('bg-white border rounded-5')
            ->setContent($return)
            ->render();

        // Render the section around it
        $return = Section::new()
            ->addClass('pb-4')
            ->setContent($return)
            ->render();

        // Render the title and header section around it
        $content = '';

        if ($this->element->getTitle()) {
            $content .= '<h2 class="mb-4">' . htmlentities($this->element->getTitle()) . '</h2>';
        }

        if ($this->element->getHeaderText()) {
            $content .= '<p>' . htmlentities($this->element->getHeaderText()) . '</p>';
        }
        
        if ($content) {
            $section = Section::new()
                ->setContent($content . $return);

            if ($this->element->getId()) {
                $section->setId('section-' . $this->element->getId());
            }

            return $section->render();
        }

        return $return;
    }
}