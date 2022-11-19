<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait ElementAttributes
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait ElementAttributes
{
    /**
     * The HTML id element attribute
     *
     * @var string|null $id
     */
    protected ?string $id = null;

    /**
     * The real HTML id element attribute. If id contains "element[]", this will contain "element"
     *
     * @var string|null $real_id
     */
    protected ?string $real_id = null;

    /**
     * The HTML name element attribute
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The real HTML name element attribute. If name contains "element[]", this will contain "element"
     *
     * @var string|null $real_name
     */
    protected ?string $real_name = null;

    /**
     * The HTML class element attribute store
     *
     * @var array $classes
     */
    protected array $classes = [];

    /**
     * The HTML class element attribute cache
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     * The HTML readonly attribute
     *
     * @var string|null $readonly
     */
    protected ?string $readonly = null;

    /**
     * The HTML disabled attribute
     *
     * @var string|null $disabled
     */
    protected ?string $disabled = null;

    /**
     * The tabindex for this element
     *
     * @var int|null
     */
    protected ?int $tabindex = null;

    /**
     * The HTML autofocus attribute
     *
     * @var string|null $autofocus
     */
    protected ?string $autofocus = null;

    /**
     * Extra attributes or element content can be added through the "extra" variable
     *
     * @var string $extra
     */
    protected string $extra = '';

    /**
     * The attributes for this element
     *
     * @var array $attributes
     */
    protected array $attributes = [];

    /**
     * The element content
     *
     * @var string|null $content
     */
    protected ?string $content = null;

    /**
     * The element height
     *
     * @var int|null $height
     */
    protected ?int $height = null;

    /**
     * The element width
     *
     * @var int|null $width
     */
    protected ?int $width = null;



    /**
     * Element class constructor
     */
    public function __construct()
    {
    }



    /**
     * Return new HTML Element object
     *
     * return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @return static
     */
    public function setId(?string $id): static
    {
        $this->id      = $id;
        $this->real_id = Strings::until($id, '[');

        // By default, name and id should be equal
        if (empty($this->name)) {
            $this->setName($id);
        }

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
     * @return static
     */
    public function setName(?string $name): static
    {
        $this->name      = $name;
        $this->real_name = Strings::until($name, '[');

        // By default, name and id should be equal
        if (empty($this->id)) {
            $this->setId($name);
        }

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
     * @param array|string|null $classes
     * @return static
     */
    public function setClasses(array|string|null $classes): static
    {
        $this->classes = [];
        return $this->addClasses($classes);
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    public function addClasses(array|string|null $classes): static
    {
        foreach (Arrays::force($classes, ' ') as $class) {
            $this->addClass($class);
        }

        return $this;
    }



    /**
     * Adds a class to the HTML class element attribute
     *
     * @param string|null $class
     * @return static
     */
    public function addClass(?string $class): static
    {
        // Only add class if specified.
        if ($class) {
            $this->classes[$class] = true;
            $this->class = null;
        }

        return $this;
    }



    /**
     * Adds an class to the HTML class element attribute
     *
     * @param string $class
     * @return static
     */
    public function setClass(string $class): static
    {
        $this->classes = [$class => true];
        $this->class = null;
        return $this;
    }



    /**
     * Returns the HTML class element attribute store
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        if (!$this->class) {
            if ($this->classes) {
                $this->class = implode(' ', array_keys($this->classes));
            } else {
                $this->class = null;
            }
        }

        return $this->class;
    }



    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return static
     */
    public function setTabIndex(?int $tabindex): static
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
        return ($this->disabled ? null : $this->tabindex);
    }



    /**
     * Sets all the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    public function setExtra(?string $extra): static
    {
        $this->extra = '';
        return $this->addExtra($extra);
    }



    /**
     * Adds more to the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    public function addExtra(?string $extra): static
    {
        $this->extra .= ' ' . $extra;
        return $this;
    }



    /**
     * Returns the extra element attribute code
     *
     * @return string
     */
    public function getExtra(): string
    {
        return $this->extra;
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param bool $autofocus
     * @return static
     */
    public function setAutofocus(bool $autofocus): static
    {
        $this->autofocus = ($autofocus ? 'autofocus' : null);
        return $this;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return bool
     */
    public function getAutofocus(): bool
    {
        return (bool) $this->autofocus;
    }



    /**
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        $this->disabled = ($disabled ? 'disabled' : null);
        return $this;
    }


    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return (bool) $this->disabled;
    }



    /**
     * Set the HTML readonly element attribute
     *
     * @param bool $readonly
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        $this->readonly = ($readonly ? 'readonly' : null);
        return $this;
    }


    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return static
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = [];
        $this->addAttributes($attributes);
        return $this;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return static
     */
    public function addAttributes(array $attributes): static
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
     * @return static
     */
    public function addAttribute(string $attribute, string $value): static
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
     * Sets the content of the element to display
     *
     * @param string|null $content
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }



    /**
     * Returns the content of the element to display
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }



    /**
     * Sets the height of the element to display
     *
     * @param int|null $height
     * @return static
     */
    public function setHeight(?int $height): static
    {
        if ($height < 0) {
            throw new OutOfBoundsException(tr('Invalid element height ":value" specified, it should be 0 or above', [
                ':value' => $height
            ]));
        }

        $this->height = $height;
        return $this;
    }



    /**
     * Returns the height of the element to display
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }



    /**
     * Sets the width of the element to display
     *
     * @param int|null $width
     * @return static
     */
    public function setWidth(?int $width): static
    {
        if ($width < 0) {
            throw new OutOfBoundsException(tr('Invalid element width ":value" specified, it should be 0 or above', [
                ':value' => $width
            ]));
        }

        $this->width = $width;
        return $this;
    }



    /**
     * Returns the width of the element to display
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }
}