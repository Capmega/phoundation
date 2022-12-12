<?php

namespace Plugins\Mdb\Components;

use Phoundation\Web\Http\Html\Elements\Section;



/**
 * MDB Plugin Table class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Table extends \Phoundation\Web\Http\Html\Elements\Table
{
    /**
     * Sets whether the table is responsive or not
     *
     * @var bool $responsive
     */
    protected bool $responsive = true;

    /**
     * Sets whether the table is full width or not
     *
     * @var bool $full_width
     */
    protected bool $full_width = true;

    /**
     * Table title
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * Table header text
     *
     * @var string|null $header_text
     */
    protected ?string $header_text = null;



    /**
     * Table class constructor
     */
    public function __construct()
    {
        $this->addClass('table');
        parent::__construct();
    }



    /**
     * Returns if the table is title or not
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }



    /**
     * Sets if the table is title or not
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }



    /**
     * Returns if the table is header_text or not
     *
     * @return string|null
     */
    public function getHeaderText(): ?string
    {
        return $this->header_text;
    }



    /**
     * Sets if the table is header_text or not
     *
     * @param string|null $header_text
     * @return static
     */
    public function setHeaderText(?string $header_text): static
    {
        $this->header_text = $header_text;
        return $this;
    }



    /**
     * Returns if the table is responsive or not
     *
     * @return bool
     */
    public function getResponsive(): bool
    {
        return $this->responsive;
    }



    /**
     * Sets if the table is responsive or not
     *
     * @param bool $responsive
     * @return static
     */
    public function setResponsive(bool $responsive): static
    {
        $this->responsive = $responsive;
        return $this;
    }



    /**
     * Returns if the table is full width or not
     *
     * @return bool
     */
    public function getFullWidth(): bool
    {
        return $this->full_width;
    }



    /**
     * Sets if the table is full width or not
     *
     * @param bool $full_width
     * @return static
     */
    public function setFullWidth(bool $full_width): static
    {
        $this->full_width = $full_width;
        return $this;
    }



    /**
     * Render the MDB table
     *
     * @return string
     */
    public function render(): string
    {
        // Render the table
        $table = parent::render();

        // Render the section around it
        $return = Section::new()
            ->addClass($this->full_width ? 'w-100' : null)
            ->addClass($this->responsive ? 'table-responsive' : null)
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

        if ($this->title) {
            $content .= '<h2 class="mb-4">' . htmlentities($this->title) . '</h2>';
        }

        if ($this->header_text) {
            $content .= '<p>' . htmlentities($this->header_text) . '</p>';
        }
        
        if ($content) {
            $section = Section::new()
                ->setContent($content . $return);

            if ($this->id) {
                $section->setId('section-' . $this->id);
            }

            return $section->render();
        }

        return $return;
    }
}