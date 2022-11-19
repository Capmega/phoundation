<?php

namespace Plugins\Mdb\Layouts;

use JetBrains\PhpStorm\ExpectedValues;



/**
 * MDB Plugin Container class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Section extends Layout
{
    /**
     * Container value for this container
     *
     * @var string|null $type
     */
    #[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])]
    protected ?string $type = null;

    /**
     * The content for this container
     *
     * @var string|null $content
     */
    protected ?string $content = null;


    /**
     * Container class constructor
     */
    public function __construct()
    {
        $this->type = 'md';
    }



    /**
     * Returns a new static object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Sets the type for this container
     *
     * @param string $type
     * @return $this
     */
    public function setType(#[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])] string $type): static
    {
        $this->type = $type;
        return $this;
    }



    /**
     * Returns the type for this container
     *
     * @return string
     */
    #[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])] public function getType(): string
    {
        return $this->type;
    }



    /**
     * Sets the content for this container
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
    }



    /**
     * Returns the content for this container
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }



    /**
     * Render the HTML for this container
     *
     * @return string
     */
    public function render(): string
    {
        return '<section class="container' . ($this->type ? '-' . $this->type : null) . '">' . $this->content . '</section>';
    }
}