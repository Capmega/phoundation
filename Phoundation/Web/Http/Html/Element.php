<?php

namespace Phoundation\Web\Http\Html;



/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Element
{
    /**
     * The element type
     *
     * @var string $type
     */
    protected string $type;

    /**
     * The HTML id element attribute
     *
     * @var string|null $id
     */
    protected ?string $id = null;

    /**
     * The HTML name element attribute
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The HTML class element attribute
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     * The HTML tabindex element attribute
     *
     * @var int|null $tabindex
     */
    protected ?int $tabindex = null;

    /**
     * The attributes for this element
     *
     * @var array $attributes
     */
    protected array $attributes = [];



    /**
     * HtmlObject constructor
     */
    public function __construct(string $type)
    {
        $this->type     = $type;
        $this->tabindex = Html::getTabIndex();
    }



    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @return Element
     */
    public function setId(?string $id): Element
    {
        $this->id = $id;
        return $this;
    }



    /**
     * Returns the HTML id element attribute
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }



    /**
     * Sets the HTML name element attribute
     *
     * @param string|null $name
     * @return Element
     */
    public function setName(?string $name): Element
    {
        $this->name = $name;
        return $this;
    }



    /**
     * Returns the HTML name element attribute
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param string|null $class
     * @return Element
     */
    public function setClass(?string $class): Element
    {
        $this->class = $class;
        return $this;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }



    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return Element
     */
    public function setTabIndex(?int $tabindex): Element
    {
        $this->tabindex = $tabindex;
        return $this;
    }


    /**
     * Returns the HTML tabindex element attribute
     *
     * @return int|null
     */
    public function getTabIndex(): ?int
    {
        return $this->tabindex;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return Element
     */
    public function setAttributes(array $attributes): Element
    {
        $this->attributes = [];
        return $this;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return Element
     */
    public function addAttributes(array $attributes): Element
    {
        foreach ($attributes as $attribute => $value) {
            $this->addAttribute($attribute, $value);
        }

        return $this;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param string $attribute
     * @param string $value
     * @return Element
     */
    public function addAttribute(string $attribute, string $value): Element
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }



    /**
     * Returns all HTML element attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }



    /**
     * Generates and returns the HTML string
     *
     * @return string
     */
    public function render(): string
    {
        return '<' . $this->type. ' ' . implode(' ', $this->buildAttributes()) . '>';
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @return array
     */
    protected function buildAttributes(): array
    {
        return array_merge($this->attributes, [
            'id'       => $this->id,
            'name'     => $this->name,
            'tabindex' => $this->tabindex,
        ]);
    }
}